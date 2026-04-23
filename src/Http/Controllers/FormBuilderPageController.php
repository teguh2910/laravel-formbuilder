<?php

namespace SatuForm\FormBuilder\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Routing\Controller;
use SatuForm\FormBuilder\Models\FormDepartment;
use SatuForm\FormBuilder\Models\FormField;
use SatuForm\FormBuilder\Models\FormSubmission;
use SatuForm\FormBuilder\Models\FormTemplate;
use SatuForm\FormBuilder\Models\FormUser;
use Throwable;

class FormBuilderPageController extends Controller
{
    private function render(string $initialView, array $initialData = []): View
    {
        $currentUser = session('formbuilder_user');

        return view('formbuilder::formbuilder', [
            'formbuilderInitialView' => $initialView,
            'formbuilderCurrentUser' => is_array($currentUser) ? $currentUser : null,
            'formbuilderInitialData' => $initialData,
            'formbuilderFlash' => session('formbuilder_flash'),
        ]);
    }

    public function landing()
    {
        return $this->render('landing');
    }

    public function login()
    {
        $currentUser = $this->sessionUser();
        if ($currentUser) {
            return redirect($this->homePathFor($currentUser));
        }

        return $this->render('login');
    }

    public function forms()
    {
        return $this->render('fillList', $this->buildPublicData());
    }

    public function formsFill(Request $request)
    {
        $payload = $this->buildPublicData();
        $payload['selectedTemplateId'] = trim((string) $request->query('template', '')) ?: null;

        return $this->render('fillForm', $payload);
    }

    public function track(Request $request)
    {
        $payload = [];
        $trackId = strtoupper(trim((string) $request->query('id', '')));
        if ($trackId !== '') {
            $submission = FormSubmission::query()->find($trackId);
            $payload['trackSubmission'] = $submission ? $this->mapSubmission($submission) : null;
            $payload['trackQuery'] = $trackId;
            $payload['trackNotFound'] = !$submission;
        }

        return $this->render('track', $payload);
    }

    public function submitPublicForm(Request $request): RedirectResponse
    {
        $rawPayload = $request->input('payload');
        $decoded = json_decode((string) $rawPayload, true);
        $payload = is_array($decoded) ? $decoded : [];

        try {
            $submission = $this->storeSubmissionFromPayload($payload);

            return redirect($this->prefixedPath('forms'))
                ->with('formbuilder_flash', [
                    'type' => 'success',
                    'message' => 'Form submitted. Tracking ID: ' . $submission->id,
                ]);
        } catch (Throwable $e) {
            $errorMessage = $e->getMessage() ?: 'Failed to submit form.';
            if ($e instanceof ValidationException) {
                $firstError = collect($e->errors())->flatten()->first();
                if (is_string($firstError) && $firstError !== '') {
                    $errorMessage = $firstError;
                }
            }

            $templateId = trim((string) ($payload['templateId'] ?? ''));
            $target = $this->prefixedPath('forms/fill');
            if ($templateId !== '') {
                $target .= '?template=' . urlencode($templateId);
            }

            return redirect($target)
                ->with('formbuilder_flash', [
                    'type' => 'error',
                    'message' => $errorMessage,
                ]);
        }
    }

    public function submitAuthenticatedForm(Request $request): RedirectResponse
    {
        $currentUser = $this->sessionUser();
        if (!$currentUser) {
            return redirect($this->loginPath())
                ->with('formbuilder_flash', [
                    'type' => 'error',
                    'message' => 'Please login first.',
                ]);
        }

        $rawPayload = $request->input('payload');
        $decoded = json_decode((string) $rawPayload, true);
        $payload = is_array($decoded) ? $decoded : [];

        $payload['employeeName'] = $currentUser['name'] ?? '';
        $payload['employeeEmail'] = $currentUser['email'] ?? '';

        try {
            $submission = $this->storeSubmissionFromPayload($payload);
            $target = $this->redirectPathForAuthenticatedSubmit($request, $currentUser);

            return redirect($target)
                ->with('formbuilder_flash', [
                    'type' => 'success',
                    'message' => 'Form submitted. Tracking ID: ' . $submission->id,
                ]);
        } catch (Throwable $e) {
            $errorMessage = $e->getMessage() ?: 'Failed to submit form.';
            if ($e instanceof ValidationException) {
                $firstError = collect($e->errors())->flatten()->first();
                if (is_string($firstError) && $firstError !== '') {
                    $errorMessage = $firstError;
                }
            }

            $target = $this->redirectPathForAuthenticatedSubmit($request, $currentUser);

            return redirect($target)
                ->with('formbuilder_flash', [
                    'type' => 'error',
                    'message' => $errorMessage,
                ]);
        }
    }

    public function admin(Request $request)
    {
        $currentUser = $this->sessionUser();
        if (!$currentUser) {
            return redirect($this->loginPath());
        }
        $role = strtolower(trim((string) ($currentUser['role'] ?? '')));
        if ($role === 'non_admin') {
            return redirect($this->prefixedPath('/my-submissions'));
        }

        $page = $this->normalizeAdminPage((string) $request->query('page', 'dashboard'));
        $isSuperadmin = $role === 'superadmin';
        $isAdminDepartment = $role === 'admin_department';
        $adminDepartmentAllowed = ['submit-form', 'my-submissions', 'forms', 'submissions', 'formEditor'];

        if ($isAdminDepartment && !in_array($page, $adminDepartmentAllowed, true)) {
            return redirect($this->prefixedPath('admin') . '?page=submit-form');
        }

        if (!$isSuperadmin && in_array($page, ['departments', 'users'], true)) {
            return redirect($this->prefixedPath('admin') . '?page=dashboard');
        }

        $data = $this->buildAuthenticatedData();
        $data['adminPage'] = $page;
        $data['adminEditorTab'] = $this->normalizeAdminEditorTab((string) $request->query('tab', 'fields'));
        $data['adminEditorDraft'] = null;

        if ($page === 'formEditor') {
            $editTemplateId = trim((string) $request->query('template', ''));
            $isNewEditor = $request->boolean('new', false);

            if ($editTemplateId !== '') {
                $template = FormTemplate::query()->with('fields')->find($editTemplateId);
                if (!$template) {
                    return redirect($this->prefixedPath('admin') . '?page=forms')
                        ->with('formbuilder_flash', [
                            'type' => 'error',
                            'message' => 'Form template not found.',
                        ]);
                }
                $data['adminEditorDraft'] = $this->mapTemplate($template);
            } elseif ($isNewEditor) {
                $data['adminEditorDraft'] = $this->newEditorDraft($currentUser);
            } else {
                return redirect($this->prefixedPath('admin') . '?page=forms');
            }
        }

        return $this->render('admin', $data);
    }

    public function mySubmissions()
    {
        $currentUser = $this->sessionUser();
        if (!$currentUser) {
            return redirect($this->loginPath());
        }

        return $this->render('mySubmissions', $this->buildAuthenticatedData());
    }

    public function authenticate(Request $request)
    {
        $payload = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $user = FormUser::query()
            ->where('username', trim((string) $payload['username']))
            ->where('password', (string) $payload['password'])
            ->first();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Invalid credentials',
                ], 401);
            }

            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'login' => 'Invalid credentials',
                ]);
        }

        $mappedUser = [
            'id' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
            'name' => $user->name,
            'email' => $user->email,
            'department' => $user->department_id,
        ];

        session(['formbuilder_user' => $mappedUser]);
        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'user' => $mappedUser,
            ]);
        }

        return redirect($this->homePathFor($mappedUser));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('formbuilder_user');
        $request->session()->regenerateToken();

        return redirect($this->prefixedPath(''));
    }

    public function saveTemplate(Request $request): RedirectResponse
    {
        $currentUser = $this->sessionUser();
        if (!$currentUser) {
            return redirect($this->loginPath());
        }

        $role = strtolower(trim((string) ($currentUser['role'] ?? '')));
        if ($role === 'non_admin') {
            return redirect($this->prefixedPath('my-submissions'));
        }

        $rawPayload = $request->input('payload');
        $decoded = json_decode((string) $rawPayload, true);
        $payload = is_array($decoded) ? $decoded : [];

        $target = $this->resolveAdminRedirectPath($request, $this->prefixedPath('admin') . '?page=forms');

        try {
            $payload = validator($payload, [
                'id' => ['required', 'string', 'max:100'],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'department' => ['nullable', 'string', Rule::exists('Form_departments', 'id')],
                'published' => ['nullable', 'boolean'],
                'prerequisiteFormId' => ['nullable', 'string'],
                'approvalFlow' => ['nullable', 'array'],
                'approvalFlow.*.id' => ['nullable', 'string', 'max:100'],
                'approvalFlow.*.role' => ['nullable', 'string', 'max:100'],
                'approvalFlow.*.approvalType' => ['nullable', Rule::in(['internal', 'external'])],
                'approvalFlow.*.internalLevel' => ['nullable', 'integer', 'min:1', 'max:8'],
                'fields' => ['nullable', 'array'],
                'fields.*.id' => ['required', 'string', 'max:100'],
                'fields.*.type' => ['required', 'string', 'max:50'],
                'fields.*.label' => ['nullable', 'string', 'max:255'],
                'fields.*.required' => ['nullable', 'boolean'],
                'fields.*.options' => ['nullable', 'array'],
                'fields.*.formula' => ['nullable', 'string'],
                'fields.*.tableColumns' => ['nullable', 'array'],
                'fields.*.tableRows' => ['nullable', 'integer', 'min:1', 'max:200'],
            ])->validate();

            $existing = FormTemplate::query()->find($payload['id']);
            if ($role === 'admin_department') {
                if ($existing && (string) ($existing->department_id ?? '') !== (string) ($currentUser['department'] ?? '')) {
                    throw ValidationException::withMessages([
                        'template' => ['You are not allowed to edit this form template.'],
                    ]);
                }
                $payload['department'] = $currentUser['department'] ?? null;
            }

            DB::transaction(function () use ($payload) {
                $template = FormTemplate::query()->firstOrNew(['id' => $payload['id']]);
                $template->name = $payload['name'];
                $template->description = $payload['description'] ?? null;
                $template->department_id = $payload['department'] ?? null;
                $template->published = (bool) ($payload['published'] ?? false);
                $template->prerequisite_form_id = $payload['prerequisiteFormId'] ?? null;
                $template->approval_flow = $payload['approvalFlow'] ?? [];
                $template->save();

                FormField::query()->where('template_id', $template->id)->delete();

                $fields = $payload['fields'] ?? [];
                foreach ($fields as $index => $field) {
                    FormField::query()->create([
                        'id' => $field['id'],
                        'template_id' => $template->id,
                        'type' => $field['type'],
                        'label' => $field['label'] ?? null,
                        'required' => (bool) ($field['required'] ?? false),
                        'options' => $field['options'] ?? null,
                        'formula' => $field['formula'] ?? null,
                        'table_columns' => $field['tableColumns'] ?? null,
                        'table_rows' => $field['tableRows'] ?? null,
                        'sort_order' => $index,
                    ]);
                }
            });

            return redirect($target)->with('formbuilder_flash', [
                'type' => 'success',
                'message' => 'Form template saved.',
            ]);
        } catch (Throwable $e) {
            return redirect($target)->with('formbuilder_flash', [
                'type' => 'error',
                'message' => $this->exceptionMessage($e, 'Failed to save template.'),
            ]);
        }
    }

    public function toggleTemplatePublish(Request $request, string $id): RedirectResponse
    {
        $currentUser = $this->sessionUser();
        if (!$currentUser) {
            return redirect($this->loginPath());
        }

        $role = strtolower(trim((string) ($currentUser['role'] ?? '')));
        if ($role === 'non_admin') {
            return redirect($this->prefixedPath('my-submissions'));
        }

        $target = $this->resolveAdminRedirectPath($request, $this->prefixedPath('admin') . '?page=forms');

        try {
            $template = FormTemplate::query()->findOrFail($id);
            if ($role === 'admin_department' && (string) ($template->department_id ?? '') !== (string) ($currentUser['department'] ?? '')) {
                throw ValidationException::withMessages([
                    'template' => ['You are not allowed to update this form template.'],
                ]);
            }

            $template->published = !$template->published;
            $template->save();

            return redirect($target)->with('formbuilder_flash', [
                'type' => 'success',
                'message' => 'Form publish status updated.',
            ]);
        } catch (Throwable $e) {
            return redirect($target)->with('formbuilder_flash', [
                'type' => 'error',
                'message' => $this->exceptionMessage($e, 'Failed to update publish status.'),
            ]);
        }
    }

    public function deleteTemplate(Request $request, string $id): RedirectResponse
    {
        $currentUser = $this->sessionUser();
        if (!$currentUser) {
            return redirect($this->loginPath());
        }

        $role = strtolower(trim((string) ($currentUser['role'] ?? '')));
        if ($role === 'non_admin') {
            return redirect($this->prefixedPath('my-submissions'));
        }

        $target = $this->resolveAdminRedirectPath($request, $this->prefixedPath('admin') . '?page=forms');

        try {
            $template = FormTemplate::query()->findOrFail($id);
            if ($role === 'admin_department' && (string) ($template->department_id ?? '') !== (string) ($currentUser['department'] ?? '')) {
                throw ValidationException::withMessages([
                    'template' => ['You are not allowed to delete this form template.'],
                ]);
            }

            $template->delete();

            return redirect($target)->with('formbuilder_flash', [
                'type' => 'success',
                'message' => 'Form template deleted.',
            ]);
        } catch (Throwable $e) {
            return redirect($target)->with('formbuilder_flash', [
                'type' => 'error',
                'message' => $this->exceptionMessage($e, 'Failed to delete template.'),
            ]);
        }
    }

    public function saveUser(Request $request): RedirectResponse
    {
        $currentUser = $this->sessionUser();
        if (!$currentUser) {
            return redirect($this->loginPath());
        }

        $role = strtolower(trim((string) ($currentUser['role'] ?? '')));
        if ($role !== 'superadmin') {
            return redirect($this->prefixedPath('admin') . '?page=dashboard');
        }

        $rawPayload = $request->input('payload');
        $decoded = json_decode((string) $rawPayload, true);
        $payload = is_array($decoded) ? $decoded : [];

        $target = $this->resolveAdminRedirectPath($request, $this->prefixedPath('admin') . '?page=users');

        try {
            $payload = validator($payload, [
                'id' => ['nullable', 'integer', Rule::exists('FormUser', 'id')],
                'username' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('FormUser', 'username')->ignore($payload['id'] ?? null),
                ],
                'password' => ['nullable', 'string', 'max:255'],
                'role' => ['required', 'string', 'max:100'],
                'name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'department' => ['nullable', 'string', Rule::exists('Form_departments', 'id')],
            ])->validate();

            $isCreate = empty($payload['id']);
            $userRole = strtolower(trim((string) ($payload['role'] ?? '')));

            if ($isCreate && empty($payload['password'])) {
                throw ValidationException::withMessages([
                    'password' => ['Password is required for new user.'],
                ]);
            }
            if ($userRole !== 'superadmin' && empty($payload['department'])) {
                throw ValidationException::withMessages([
                    'department' => ['Department is required for non-superadmin user.'],
                ]);
            }

            $user = $isCreate
                ? new FormUser()
                : FormUser::query()->findOrFail((int) $payload['id']);

            $user->username = $payload['username'];
            $user->role = $userRole;
            $user->name = $payload['name'];
            $user->email = $payload['email'] ?? null;
            $user->department_id = $userRole === 'superadmin'
                ? null
                : ($payload['department'] ?? null);
            if (!empty($payload['password'])) {
                $user->password = $payload['password'];
            }
            $user->save();

            return redirect($target)->with('formbuilder_flash', [
                'type' => 'success',
                'message' => 'User saved.',
            ]);
        } catch (Throwable $e) {
            return redirect($target)->with('formbuilder_flash', [
                'type' => 'error',
                'message' => $this->exceptionMessage($e, 'Failed to save user.'),
            ]);
        }
    }

    public function deleteUser(Request $request, int $id): RedirectResponse
    {
        $currentUser = $this->sessionUser();
        if (!$currentUser) {
            return redirect($this->loginPath());
        }

        $role = strtolower(trim((string) ($currentUser['role'] ?? '')));
        if ($role !== 'superadmin') {
            return redirect($this->prefixedPath('admin') . '?page=dashboard');
        }

        $target = $this->resolveAdminRedirectPath($request, $this->prefixedPath('admin') . '?page=users');

        try {
            $user = FormUser::query()->findOrFail($id);
            if (strtolower(trim((string) $user->role)) === 'superadmin') {
                throw ValidationException::withMessages([
                    'user' => ['Superadmin user cannot be deleted.'],
                ]);
            }

            $user->delete();

            return redirect($target)->with('formbuilder_flash', [
                'type' => 'success',
                'message' => 'User deleted.',
            ]);
        } catch (Throwable $e) {
            return redirect($target)->with('formbuilder_flash', [
                'type' => 'error',
                'message' => $this->exceptionMessage($e, 'Failed to delete user.'),
            ]);
        }
    }

    public function reviewSubmission(Request $request, string $id): RedirectResponse
    {
        $currentUser = $this->sessionUser();
        if (!$currentUser) {
            return redirect($this->loginPath());
        }

        $role = strtolower(trim((string) ($currentUser['role'] ?? '')));
        if ($role === 'non_admin') {
            return redirect($this->prefixedPath('my-submissions'));
        }

        $target = $this->resolveAdminRedirectPath($request, $this->prefixedPath('admin') . '?page=submissions');

        try {
            $payload = $request->validate([
                'action' => ['required', Rule::in(['approved', 'rejected'])],
                'comments' => ['nullable', 'string', 'max:2000'],
            ]);

            $submission = FormSubmission::query()->findOrFail($id);
            $steps = $submission->approval_steps ?? [];

            if (!is_array($steps) || count($steps) === 0) {
                throw ValidationException::withMessages([
                    'submission' => ['This submission has no approval steps.'],
                ]);
            }

            $activeIndex = null;
            foreach ($steps as $index => $step) {
                if (($step['status'] ?? null) === 'in_review') {
                    $activeIndex = $index;
                    break;
                }
            }

            if ($activeIndex === null) {
                throw ValidationException::withMessages([
                    'submission' => ['No active approval step found.'],
                ]);
            }

            $reviewerUsername = strtolower(trim((string) ($currentUser['username'] ?? '')));
            $requiredRole = strtolower(trim((string) ($steps[$activeIndex]['role'] ?? '')));
            $requiredApproverUsername = strtolower(trim((string) ($steps[$activeIndex]['approverUsername'] ?? '')));

            if ($role !== 'superadmin') {
                if ($requiredApproverUsername !== '' && $reviewerUsername !== $requiredApproverUsername) {
                    throw ValidationException::withMessages([
                        'submission' => ['You are not allowed to review this step.'],
                    ]);
                }
                if ($requiredApproverUsername === '' && $requiredRole !== '' && $role !== $requiredRole) {
                    throw ValidationException::withMessages([
                        'submission' => ['You are not allowed to review this step.'],
                    ]);
                }
            }

            $now = now()->toISOString();
            $steps[$activeIndex]['status'] = $payload['action'];
            $steps[$activeIndex]['reviewedAt'] = $now;
            $steps[$activeIndex]['reviewedBy'] = $currentUser['name'] ?? null;
            $steps[$activeIndex]['comments'] = $payload['comments'] ?? '';

            if ($payload['action'] === 'approved') {
                $nextPendingIndex = null;
                for ($i = $activeIndex + 1; $i < count($steps); $i++) {
                    if (($steps[$i]['status'] ?? null) === 'pending') {
                        $nextPendingIndex = $i;
                        break;
                    }
                }

                if ($nextPendingIndex !== null) {
                    $steps[$nextPendingIndex]['status'] = 'in_review';
                    $submission->status = 'in_review';
                } else {
                    $submission->status = 'approved';
                }
            } else {
                $submission->status = 'rejected';
            }

            $submission->approval_steps = $steps;
            $submission->save();

            return redirect($target)->with('formbuilder_flash', [
                'type' => 'success',
                'message' => 'Submission ' . $payload['action'] . '.',
            ]);
        } catch (Throwable $e) {
            return redirect($target)->with('formbuilder_flash', [
                'type' => 'error',
                'message' => $this->exceptionMessage($e, 'Failed to review submission.'),
            ]);
        }
    }

    private function sessionUser(): ?array
    {
        $user = session('formbuilder_user');

        return is_array($user) ? $user : null;
    }

    private function prefixedPath(string $suffix): string
    {
        $prefix = trim((string) config('formbuilder.route_prefix', 'formbuilder'), '/');
        $suffix = trim($suffix, '/');

        if ($suffix === '') {
            return '/' . $prefix;
        }

        return '/' . $prefix . '/' . $suffix;
    }

    private function loginPath(): string
    {
        return $this->prefixedPath('login');
    }

    private function homePathFor(array $user): string
    {
        $role = strtolower(trim((string) ($user['role'] ?? '')));

        if ($role === 'non_admin') {
            return $this->prefixedPath('my-submissions');
        }

        return $this->prefixedPath('admin');
    }

    private function redirectPathForAuthenticatedSubmit(Request $request, array $user): string
    {
        $target = trim((string) $request->input('redirect_to', ''));
        if ($target === 'admin') {
            return $this->prefixedPath('admin');
        }
        if ($target === 'my-submissions') {
            return $this->prefixedPath('my-submissions');
        }

        return $this->homePathFor($user);
    }

    private function resolveAdminRedirectPath(Request $request, string $fallback): string
    {
        $target = trim((string) $request->input('redirect_to', ''));
        if ($target === '') {
            return $fallback;
        }

        $adminPrefix = $this->prefixedPath('admin');
        if (!str_starts_with($target, $adminPrefix)) {
            return $fallback;
        }

        return $target;
    }

    private function exceptionMessage(Throwable $e, string $default): string
    {
        if ($e instanceof ValidationException) {
            $firstError = collect($e->errors())->flatten()->first();
            if (is_string($firstError) && $firstError !== '') {
                return $firstError;
            }
        }

        $message = trim((string) $e->getMessage());
        return $message !== '' ? $message : $default;
    }

    private function buildPublicData(): array
    {
        return [
            'depts' => FormDepartment::query()->orderBy('name')->get()->map(function (FormDepartment $dept) {
                return [
                    'id' => $dept->id,
                    'name' => $dept->name,
                    'code' => $dept->code,
                ];
            })->values()->all(),
            'templates' => FormTemplate::query()
                ->with('fields')
                ->where('published', true)
                ->orderBy('created_at')
                ->get()
                ->map(fn (FormTemplate $template) => $this->mapTemplate($template))
                ->values()
                ->all(),
            'submissions' => FormSubmission::query()
                ->select(['id', 'template_id', 'status'])
                ->orderByDesc('submitted_at')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (FormSubmission $submission) => $this->mapSubmissionForPrerequisite($submission))
                ->values()
                ->all(),
        ];
    }

    private function buildAuthenticatedData(): array
    {
        return [
            'users' => FormUser::query()
                ->orderBy('id')
                ->get()
                ->map(fn (FormUser $user) => $this->mapUser($user))
                ->values()
                ->all(),
            'depts' => FormDepartment::query()->orderBy('name')->get()->map(function (FormDepartment $dept) {
                return [
                    'id' => $dept->id,
                    'name' => $dept->name,
                    'code' => $dept->code,
                ];
            })->values()->all(),
            'templates' => FormTemplate::query()
                ->with('fields')
                ->orderBy('created_at')
                ->get()
                ->map(fn (FormTemplate $template) => $this->mapTemplate($template))
                ->values()
                ->all(),
            'submissions' => FormSubmission::query()
                ->orderByDesc('submitted_at')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (FormSubmission $submission) => $this->mapSubmission($submission))
                ->values()
                ->all(),
        ];
    }

    private function mapUser(FormUser $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
            'name' => $user->name,
            'email' => $user->email,
            'department' => $user->department_id,
        ];
    }

    private function mapTemplate(FormTemplate $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'description' => $template->description,
            'department' => $template->department_id,
            'published' => (bool) $template->published,
            'prerequisiteFormId' => $template->prerequisite_form_id,
            'approvalFlow' => $template->approval_flow ?? [],
            'fields' => $template->fields->sortBy('sort_order')->values()->map(function (FormField $field) {
                return [
                    'id' => $field->id,
                    'type' => $field->type,
                    'label' => $field->label,
                    'required' => (bool) $field->required,
                    'options' => $field->options,
                    'formula' => $field->formula,
                    'tableColumns' => $field->table_columns,
                    'tableRows' => $field->table_rows,
                ];
            })->all(),
        ];
    }

    private function mapSubmission(FormSubmission $submission): array
    {
        return [
            'id' => $submission->id,
            'templateId' => $submission->template_id,
            'templateName' => $submission->template_name,
            'department' => $submission->department_id,
            'employeeName' => $submission->employee_name,
            'employeeEmail' => $submission->employee_email,
            'data' => $submission->data ?? [],
            'approvalSteps' => $submission->approval_steps ?? [],
            'status' => $submission->status,
            'submittedAt' => optional($submission->submitted_at)->toISOString() ?? optional($submission->created_at)->toISOString(),
        ];
    }

    private function mapSubmissionForPrerequisite(FormSubmission $submission): array
    {
        return [
            'id' => $submission->id,
            'templateId' => $submission->template_id,
            'status' => $submission->status,
        ];
    }

    private function normalizeAdminPage(string $page): string
    {
        $allowed = [
            'dashboard',
            'submit-form',
            'my-submissions',
            'forms',
            'submissions',
            'tracking',
            'departments',
            'users',
            'formEditor',
        ];

        return in_array($page, $allowed, true) ? $page : 'dashboard';
    }

    private function normalizeAdminEditorTab(string $tab): string
    {
        $allowed = ['fields', 'approval', 'settings', 'preview'];
        return in_array($tab, $allowed, true) ? $tab : 'fields';
    }

    private function newEditorDraft(array $currentUser): array
    {
        return [
            'id' => $this->generateTplId(),
            'name' => '',
            'description' => '',
            'department' => (($currentUser['role'] ?? '') === 'superadmin')
                ? ''
                : ($currentUser['department'] ?? ''),
            'published' => false,
            'approvalFlow' => [],
            'fields' => [],
        ];
    }

    private function generateTplId(): string
    {
        $time = strtoupper(base_convert((string) time(), 10, 36));
        $rand = strtoupper(substr(bin2hex(random_bytes(3)), 0, 3));

        return 'TPL-' . $time . '-' . $rand;
    }

    private function storeSubmissionFromPayload(array $payload): FormSubmission
    {
        $id = trim((string) ($payload['id'] ?? ''));
        $templateId = trim((string) ($payload['templateId'] ?? ''));
        $employeeName = trim((string) ($payload['employeeName'] ?? ''));
        $employeeEmail = trim((string) ($payload['employeeEmail'] ?? ''));

        if ($id === '' || $templateId === '' || $employeeName === '' || $employeeEmail === '') {
            throw ValidationException::withMessages([
                'payload' => ['Invalid submission payload.'],
            ]);
        }

        $template = FormTemplate::query()->with('fields')->find($templateId);
        if (!$template) {
            throw ValidationException::withMessages([
                'templateId' => ['Template not found.'],
            ]);
        }

        if (!empty($template->prerequisite_form_id)) {
            $prerequisiteSubmissionId = strtoupper(trim((string) ($payload['prerequisiteSubmissionId'] ?? '')));
            if ($prerequisiteSubmissionId === '') {
                throw ValidationException::withMessages([
                    'prerequisiteSubmissionId' => ['Prerequisite submission ID is required for this form.'],
                ]);
            }

            $prereqSubmission = FormSubmission::query()->find($prerequisiteSubmissionId);
            if (!$prereqSubmission) {
                throw ValidationException::withMessages([
                    'prerequisiteSubmissionId' => ['Prerequisite submission ID not found.'],
                ]);
            }

            if ($prereqSubmission->template_id !== $template->prerequisite_form_id) {
                $prereqTemplate = FormTemplate::query()->find($template->prerequisite_form_id);
                throw ValidationException::withMessages([
                    'prerequisiteSubmissionId' => ['Submission ID is not from required prerequisite form: ' . ($prereqTemplate?->name ?? $template->prerequisite_form_id)],
                ]);
            }

            if ($prereqSubmission->status !== 'approved') {
                throw ValidationException::withMessages([
                    'prerequisiteSubmissionId' => ['Prerequisite submission is not approved yet.'],
                ]);
            }
        }

        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        foreach ($template->fields as $field) {
            if (!(bool) $field->required) {
                continue;
            }

            $value = $data[$field->id] ?? null;
            if ($this->isEmptyFieldValue($value)) {
                throw ValidationException::withMessages([
                    'data' => ['Field required: ' . ($field->label ?: $field->id)],
                ]);
            }
        }

        $approvalSteps = $this->buildApprovalSteps(
            $template,
            is_array($payload['approverSelections'] ?? null) ? $payload['approverSelections'] : [],
            is_array($payload['approvalSteps'] ?? null) ? $payload['approvalSteps'] : []
        );
        $status = count($approvalSteps) > 0 ? 'in_review' : 'approved';

        return FormSubmission::query()->create([
            'id' => $id,
            'template_id' => $template->id,
            'template_name' => $template->name,
            'department_id' => $template->department_id,
            'employee_name' => $employeeName,
            'employee_email' => $employeeEmail,
            'data' => $data,
            'approval_steps' => $approvalSteps,
            'status' => $status,
            'submitted_at' => $payload['submittedAt'] ?? now(),
        ]);
    }

    private function isEmptyFieldValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return count($value) === 0;
        }

        return false;
    }

    private function buildApprovalSteps(FormTemplate $template, array $approverSelections, array $fallbackApprovalSteps = []): array
    {
        $approvalFlow = is_array($template->approval_flow) ? $template->approval_flow : [];
        if (count($approvalFlow) === 0) {
            return [];
        }

        $fallbackById = [];
        foreach ($fallbackApprovalSteps as $step) {
            if (!is_array($step)) {
                continue;
            }
            $stepId = trim((string) ($step['id'] ?? ''));
            if ($stepId !== '') {
                $fallbackById[$stepId] = $step;
            }
        }

        $approverUsers = FormUser::query()
            ->get()
            ->filter(fn (FormUser $user) => strtolower(trim((string) $user->role)) !== 'non_admin')
            ->keyBy(fn (FormUser $user) => strtolower(trim((string) $user->username)));

        $steps = [];
        foreach ($approvalFlow as $index => $flowStep) {
            if (!is_array($flowStep)) {
                continue;
            }

            $stepId = trim((string) ($flowStep['id'] ?? ('APR-' . ($index + 1))));
            $role = trim((string) ($flowStep['role'] ?? $flowStep['title'] ?? $flowStep['name'] ?? 'spv'));
            $approvalType = ($flowStep['approvalType'] ?? 'internal') === 'external' ? 'external' : 'internal';

            $approverUsername = null;
            $approverName = null;

            if ($approvalType === 'internal') {
                $selectedApprover = trim((string) (
                    $approverSelections[$stepId]
                    ?? $approverSelections[(string) $index]
                    ?? ($fallbackById[$stepId]['approverUsername'] ?? '')
                    ?? ($fallbackApprovalSteps[$index]['approverUsername'] ?? '')
                ));
                $selectedApprover = strtolower($selectedApprover);

                if ($selectedApprover === '') {
                    throw ValidationException::withMessages([
                        'approverSelections' => ['Please select approver for ' . $role . '.'],
                    ]);
                }

                $approver = $approverUsers->get($selectedApprover);
                if (!$approver) {
                    throw ValidationException::withMessages([
                        'approverSelections' => ['Approver "' . $selectedApprover . '" is not valid.'],
                    ]);
                }

                $approverUsername = $approver->username;
                $approverName = $approver->name;
            } else {
                $approverName = $role !== '' ? $role : null;
            }

            $steps[] = [
                'id' => $stepId,
                'role' => $role,
                'approvalType' => $approvalType,
                'order' => $index,
                'status' => $index === 0 ? 'in_review' : 'pending',
                'approverUsername' => $approverUsername,
                'approverName' => $approverName,
            ];
        }

        return $steps;
    }
}
