<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Services\Email\EmailTemplateService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmailTemplateController extends Controller
{
    protected $service;

    public function __construct(EmailTemplateService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json(EmailTemplate::all());
    }

    public function show(EmailTemplate $template)
    {
        return response()->json($template);
    }

    public function update(Request $request, EmailTemplate $template)
    {
        // Locking Rules Enforcement
        if ($template->is_locked) {
            // Block sensitive field updates
            if ($request->hasAny(['slug', 'variable_schema', 'type', 'is_locked'])) {
                throw ValidationException::withMessages(['slug' => 'Cannot modify core settings of a locked template.']);
            }

            $validated = $request->validate([
                'subject' => 'required|string|max:255',
                'content_html' => 'required|string',
                'content_text' => 'nullable|string',
                'description' => 'nullable|string',
            ]);
        } else {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'slug' => 'sometimes|string|max:255|unique:email_templates,slug,' . $template->id,
                'subject' => 'required|string|max:255',
                'content_html' => 'required|string',
                'content_text' => 'nullable|string',
                'variable_schema' => 'nullable|array',
                'description' => 'nullable|string',
                'type' => 'sometimes|string',
            ]);
        }

        // Content Validation against Schema
        // We use the EXISTING schema for locked templates, or the NEW schema for unlocked ones
        $schema = $template->is_locked ? $template->variable_schema : ($request->input('variable_schema') ?? $template->variable_schema);

        try {
            $this->service->validateTemplateContent($validated['content_html'], $schema ?? []);
        } catch (\Exception $e) {
            throw ValidationException::withMessages(['content_html' => $e->getMessage()]);
        }

        $template->update($validated);

        return response()->json($template);
    }

    public function preview(Request $request, EmailTemplate $template)
    {
        $preview = $this->service->preview($template);
        return response()->json($preview);
    }
}
