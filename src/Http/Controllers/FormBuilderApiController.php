<?php

namespace SatuForm\FormBuilder\Http\Controllers;

use SatuForm\FormBuilder\Models\FormDepartment;
use SatuForm\FormBuilder\Models\FormField;
use SatuForm\FormBuilder\Models\FormSubmission;
use SatuForm\FormBuilder\Models\FormTemplate;
use SatuForm\FormBuilder\Models\FormUser;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FormBuilderApiController extends Controller
{
    public function login(Request $request)
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
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        return response()->json([
            'ok' => true,
            'user' => $this->mapUser($user),
        ]);
    }

    public function bootstrap()
    {
        $includeUsers = request()->boolean('includeUsers', false);

        return response()->json([
            'users' => $includeUsers
                ? FormUser::query()->orderBy('id')->get()->map(fn (FormUser $user) => $this->mapUser($user))
                : [],
            'depts' => FormDepartment::query()->orderBy('name')->get()->map(function (FormDepartment $dept) {
                return [
                    'id' => $dept->id,
                    'name' => $dept->name,
                    'code' => $dept->code,
                ];
            }),
            'templates' => FormTemplate::query()->with('fields')->orderBy('created_at')->get()->map(fn (FormTemplate $t) => $this->mapTemplate($t)),
            'submissions' => FormSubmission::query()->orderByDesc('submitted_at')->orderByDesc('created_at')->get()->map(fn (FormSubmission $s) => $this->mapSubmission($s)),
        ]);
    }

    public function saveUser(Request $request)
    {
        $payload = $request->validate([
            'id' => ['nullable', 'integer', Rule::exists('FORM.form_users', 'id')],
            'username' => [
                'required',
                'string',
                'max:100',
                Rule::unique('FORM.form_users', 'username')->ignore($request->input('id')),
            ],
            'password' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'department' => ['nullable', 'string', Rule::exists('FORM.form_departments', 'id')],
        ]);

        $isCreate = empty($payload['id']);
        $role = strtolower(trim((string) ($payload['role'] ?? '')));

        if ($isCreate && empty($payload['password'])) {
            return response()->json([
                'message' => 'Password is required for new user.',
            ], 422);
        }

        if ($role !== 'superadmin' && empty($payload['department'])) {
            return response()->json([
                'message' => 'Department is required for non-superadmin user.',
            ], 422);
        }

        $user = $isCreate
            ? new FormUser()
            : FormUser::query()->findOrFail((int) $payload['id']);

        $user->username = $payload['username'];
        $user->role = $role;
        $user->name = $payload['name'];
        $user->email = $payload['email'] ?? null;
        $user->department_id = $role === 'superadmin'
            ? null
            : ($payload['department'] ?? null);

        if (!empty($payload['password'])) {
            $user->password = $payload['password'];
        }

        $user->save();

        return response()->json([
            'ok' => true,
            'user' => $this->mapUser($user),
        ]);
    }

    public function deleteUser(int $id)
    {
        $user = FormUser::query()->findOrFail($id);

        if ($user->role === 'superadmin') {
            return response()->json([
                'message' => 'Superadmin user cannot be deleted.',
            ], 422);
        }

        $user->delete();

        return response()->json(['ok' => true]);
    }

    public function saveTemplate(Request $request)
    {
        $payload = $request->validate([
            'id' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'department' => ['nullable', 'string', Rule::exists('FORM.form_departments', 'id')],
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
        ]);

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

        $saved = FormTemplate::query()->with('fields')->findOrFail($payload['id']);
        return response()->json([
            'ok' => true,
            'template' => $this->mapTemplate($saved),
        ]);
    }

    public function toggleTemplatePublish(string $id)
    {
        $template = FormTemplate::query()->findOrFail($id);
        $template->published = !$template->published;
        $template->save();

        return response()->json([
            'ok' => true,
            'published' => $template->published,
        ]);
    }

    public function deleteTemplate(string $id)
    {
        $template = FormTemplate::query()->findOrFail($id);
        $template->delete();

        return response()->json(['ok' => true]);
    }

    public function storeSubmission(Request $request)
    {
        $payload = $request->validate([
            'id' => ['required', 'string', 'max:100'],
            'templateId' => ['required', 'string'],
            'employeeName' => ['required', 'string', 'max:255'],
            'employeeEmail' => ['required', 'email', 'max:255'],
            'data' => ['nullable', 'array'],
            'prerequisiteSubmissionId' => ['nullable', 'string', 'max:100'],
            'approverSelections' => ['nullable', 'array'],
            'approverSelections.*' => ['nullable', 'string', 'max:100'],
            'approvalSteps' => ['nullable', 'array'],
            'submittedAt' => ['nullable', 'date'],
        ]);

        $template = FormTemplate::query()->find($payload['templateId']);
        if (!$template) {
            return response()->json([
                'message' => 'Template not found.',
            ], 422);
        }

        $template->loadMissing('fields');

        if (!empty($template->prerequisite_form_id)) {
            $prerequisiteSubmissionId = strtoupper(trim((string) ($payload['prerequisiteSubmissionId'] ?? '')));
            if ($prerequisiteSubmissionId === '') {
                return response()->json([
                    'message' => 'Prerequisite submission ID is required for this form.',
                ], 422);
            }

            $prereqSubmission = FormSubmission::query()->find($prerequisiteSubmissionId);
            if (!$prereqSubmission) {
                return response()->json([
                    'message' => 'Prerequisite submission ID not found.',
                ], 422);
            }

            if ($prereqSubmission->template_id !== $template->prerequisite_form_id) {
                $prereqTemplate = FormTemplate::query()->find($template->prerequisite_form_id);
                return response()->json([
                    'message' => 'Submission ID is not from required prerequisite form: ' . ($prereqTemplate?->name ?? $template->prerequisite_form_id),
                ], 422);
            }

            if ($prereqSubmission->status !== 'approved') {
                return response()->json([
                    'message' => 'Prerequisite submission is not approved yet.',
                ], 422);
            }
        }

        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        foreach ($template->fields as $field) {
            if (!(bool) $field->required) {
                continue;
            }

            $value = $data[$field->id] ?? null;
            if ($this->isEmptyFieldValue($value)) {
                return response()->json([
                    'message' => 'Field required: ' . ($field->label ?: $field->id),
                ], 422);
            }
        }

        $approvalSteps = $this->buildApprovalSteps(
            $template,
            $payload['approverSelections'] ?? [],
            $payload['approvalSteps'] ?? []
        );
        $status = count($approvalSteps) > 0 ? 'in_review' : 'approved';

        $submission = FormSubmission::query()->create([
            'id' => $payload['id'],
            'template_id' => $template->id,
            'template_name' => $template->name,
            'department_id' => $template->department_id,
            'employee_name' => $payload['employeeName'],
            'employee_email' => $payload['employeeEmail'],
            'data' => $data,
            'approval_steps' => $approvalSteps,
            'status' => $status,
            'submitted_at' => $payload['submittedAt'] ?? now(),
        ]);

        return response()->json([
            'ok' => true,
            'submission' => $this->mapSubmission($submission),
        ]);
    }

    public function showSubmission(string $id)
    {
        $submission = FormSubmission::query()->find($id);
        if (!$submission) {
            return response()->json(['ok' => false, 'message' => 'Submission not found'], 404);
        }

        return response()->json([
            'ok' => true,
            'submission' => $this->mapSubmission($submission),
        ]);
    }

    public function reviewSubmission(Request $request, string $id)
    {
        $payload = $request->validate([
            'action' => ['required', Rule::in(['approved', 'rejected'])],
            'reviewerRole' => ['required', 'string', 'max:100'],
            'reviewerUsername' => ['nullable', 'string', 'max:100'],
            'reviewerName' => ['nullable', 'string', 'max:255'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        $submission = FormSubmission::query()->findOrFail($id);
        $steps = $submission->approval_steps ?? [];

        if (!is_array($steps) || count($steps) === 0) {
            return response()->json([
                'message' => 'This submission has no approval steps.',
            ], 422);
        }

        $activeIndex = null;
        foreach ($steps as $index => $step) {
            if (($step['status'] ?? null) === 'in_review') {
                $activeIndex = $index;
                break;
            }
        }

        if ($activeIndex === null) {
            return response()->json([
                'message' => 'No active approval step found.',
            ], 422);
        }

        $reviewerRole = strtolower(trim((string) $payload['reviewerRole']));
        $reviewerUsername = strtolower(trim((string) ($payload['reviewerUsername'] ?? '')));
        $requiredRole = strtolower(trim((string) ($steps[$activeIndex]['role'] ?? '')));
        $requiredApproverUsername = strtolower(trim((string) ($steps[$activeIndex]['approverUsername'] ?? '')));

        if ($reviewerRole !== 'superadmin') {
            if ($requiredApproverUsername !== '' && $reviewerUsername !== $requiredApproverUsername) {
                return response()->json([
                    'message' => 'You are not allowed to review this step.',
                ], 403);
            }
            if ($requiredApproverUsername === '' && $requiredRole !== '' && $reviewerRole !== $requiredRole) {
                return response()->json([
                    'message' => 'You are not allowed to review this step.',
                ], 403);
            }
        }

        $now = now()->toISOString();
        $steps[$activeIndex]['status'] = $payload['action'];
        $steps[$activeIndex]['reviewedAt'] = $now;
        $steps[$activeIndex]['reviewedBy'] = $payload['reviewerName'] ?? null;
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

        return response()->json([
            'ok' => true,
            'submission' => $this->mapSubmission($submission->fresh()),
        ]);
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
}
