<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\TranslationException;
use App\Services\TranslationService;

class TranslationController extends BaseController
{
    public function __construct(
        private readonly TranslationService $translationService
    ) {}

    public function translate(Request $request): Response
    {
        $body       = $request->post('body_html', '');
        $subject    = $request->post('subject', '');
        $targetLang = $request->post('target_lang', '');

        if (empty($targetLang)) {
            return Response::html('')->htmxTrigger('showToast', ['type' => 'warning', 'message' => 'Please select a language.']);
        }

        if (empty(trim($body)) && empty(trim($subject))) {
            return Response::html('')->htmxTrigger('showToast', ['type' => 'info', 'message' => 'Nothing to translate.']);
        }

        try {
            $translatedBody    = $this->translationService->translateBody($body, $targetLang);
            $translatedSubject = $this->translationService->translatePlaintext($subject, $targetLang);

            $html = $this->view('compose/_translation-result', [
                'bodyHtml'        => $translatedBody,
                'subject'         => $translatedSubject,
                'originalBody'    => $body,
                'originalSubject' => $subject,
            ])->getContent();

            return Response::html($html)
                ->htmxTrigger('showToast', ['type' => 'success', 'message' => 'Email translated successfully.']);

        } catch (TranslationException $e) {
            return Response::html('')
                ->htmxTrigger('showToast', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function revert(Request $request): Response
    {
        $originalBody    = $request->post('original_body', '');
        $originalSubject = $request->post('original_subject', '');

        $html = $this->view('compose/_translation-result', [
            'bodyHtml'        => $originalBody,
            'subject'         => $originalSubject,
            'originalBody'    => null,
            'originalSubject' => null,
        ])->getContent();

        return Response::html($html)
            ->htmxTrigger('showToast', ['type' => 'info', 'message' => 'Translation reverted.']);
    }
}