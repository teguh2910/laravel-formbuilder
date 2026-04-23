<?php

namespace SatuForm\FormBuilder\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
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

    public function admin()
    {
        $currentUser = $this->sessionUser();
        if (!$currentUser) {
            return redirect($this->loginPath());
        }
        if (($currentUser['role'] ?? '') === 'non_admin') {
            return redirect($this->prefixedPath('/my-submissions'));
        }

        return $this->render('admin');
    }

    public function mySubmissions()
    {
        $currentUser = $this->sessionUser();
        if (!$currentUser) {
            return redirect($this->loginPath());
        }

        return $this->render('mySubmissions');
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
