<?php
/**
 * Step 7 — AI Readiness Review™: Laravel-backed load / generate / leadership context.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_xfarp_ai_review_load', function (): void {
    check_ajax_referer('xfarp_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $arpId = isset($_GET['arp_id']) ? absint($_GET['arp_id']) : 0;
    if ($arpId < 1) {
        wp_send_json_error(['message' => 'arp_id is required.'], 422);
    }

    xfarp_picker_send(xfarp_picker_api_request('GET', "/{$arpId}/readiness-review", [
        'user_id' => get_current_user_id(),
    ]));
});

add_action('wp_ajax_xfarp_ai_review_generate', function (): void {
    check_ajax_referer('xfarp_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $arpId = isset($_POST['arp_id']) ? absint($_POST['arp_id']) : 0;
    if ($arpId < 1) {
        wp_send_json_error(['message' => 'arp_id is required.'], 422);
    }

    // #region agent log
    $laravelHost = (string) (wp_parse_url(XFUSION_LARAVEL_API_BASE, PHP_URL_HOST) ?: '');
    $wpLlmUrlSet = function_exists('xfusion_llm_api_url') && xfusion_llm_api_url() !== '';
    $wpLlmKeySet = function_exists('xfusion_llm_api_key') && xfusion_llm_api_key() !== '';
    $bearerSet = defined('XFUSION_API_BEARER_TOKEN') && XFUSION_API_BEARER_TOKEN !== '';
    // #endregion

    $result = xfarp_picker_api_request('POST', "/{$arpId}/readiness-review/generate", [], [
        'user_id' => get_current_user_id(),
    ]);

    // #region agent log
    $bodyMsg = '';
    if (is_array($result['body'])) {
        $bodyMsg = (string) ($result['body']['message'] ?? '');
    }
    $agentDebug = [
        'hypothesisId' => 'A,B,C,D,E',
        'arp_id' => $arpId,
        'laravel_host' => $laravelHost,
        'http_code' => (int) ($result['code'] ?? 0),
        'ok' => ! empty($result['ok']),
        'wp_error' => $result['error'],
        'body_message' => $bodyMsg,
        'body_success' => is_array($result['body']) ? ($result['body']['success'] ?? null) : null,
        'wp_llm_url_configured' => $wpLlmUrlSet,
        'wp_llm_key_configured' => $wpLlmKeySet,
        'bearer_configured' => $bearerSet,
        'mentions_laravel_env' => (stripos($bodyMsg, 'Laravel .env') !== false),
        'mentions_llm_config' => (stripos($bodyMsg, 'XFUSION_LLM_API') !== false),
    ];
    if (is_array($result['body'])) {
        $result['body']['_agent_debug'] = $agentDebug;
    } else {
        $result['body'] = [
            'success' => false,
            'message' => $result['error'] ?? 'Request failed.',
            '_agent_debug' => $agentDebug,
        ];
    }
    // #endregion

    xfarp_picker_send($result);
});

add_action('wp_ajax_xfarp_ai_review_save_context', function (): void {
    check_ajax_referer('xfarp_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $arpId = isset($_POST['arp_id']) ? absint($_POST['arp_id']) : 0;
    if ($arpId < 1) {
        wp_send_json_error(['message' => 'arp_id is required.'], 422);
    }

    $context = isset($_POST['leadership_context'])
        ? sanitize_textarea_field(wp_unslash($_POST['leadership_context']))
        : '';

    xfarp_picker_send(xfarp_picker_api_request('PATCH', "/{$arpId}/readiness-review/context", [], [
        'user_id' => get_current_user_id(),
        'leadership_context' => $context,
    ]));
});

function xfarp_wizard_ai_review_service_js(): string
{
    return <<<'JS'
window.xarLoadAiReview = function () {
    if (!window.XFARP_WIZARD || !window.XFARP_WIZARD.arpId) {
        return Promise.resolve(null);
    }
    var params = new URLSearchParams();
    params.set('action', 'xfarp_ai_review_load');
    params.set('nonce', window.XFARP_WIZARD.nonce);
    params.set('arp_id', String(window.XFARP_WIZARD.arpId));

    return fetch(window.XFARP_WIZARD.ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            if (!json || !json.success || !json.data) {
                return null;
            }
            window.xarAiReviewCache = json.data;
            return json.data;
        })
        .catch(function () { return null; });
};

window.xarGenerateAiReview = function () {
    if (!window.XFARP_WIZARD || !window.XFARP_WIZARD.arpId) {
        return Promise.reject(new Error('No ARP selected.'));
    }
    var payload = new URLSearchParams();
    payload.set('action', 'xfarp_ai_review_generate');
    payload.set('nonce', window.XFARP_WIZARD.nonce);
    payload.set('arp_id', String(window.XFARP_WIZARD.arpId));

    // #region agent log
    fetch('http://127.0.0.1:7509/ingest/a40c7f35-d10e-4d02-805b-56bc5f9ff441',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'2a9b38'},body:JSON.stringify({sessionId:'2a9b38',runId:'pre-fix',hypothesisId:'D',location:'arp-ai-review-service.php:xarGenerateAiReview:start',message:'Generate AI Insights clicked',data:{arpId:window.XFARP_WIZARD.arpId,ajaxUrl:window.XFARP_WIZARD.ajaxUrl||''},timestamp:Date.now()})}).catch(function(){});
    // #endregion

    return fetch(window.XFARP_WIZARD.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); }).then(function (json) {
        // #region agent log
        var dbg = (json && json._agent_debug) ? json._agent_debug : null;
        fetch('http://127.0.0.1:7509/ingest/a40c7f35-d10e-4d02-805b-56bc5f9ff441',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'2a9b38'},body:JSON.stringify({sessionId:'2a9b38',runId:'pre-fix',hypothesisId:'A,B,C,D,E',location:'arp-ai-review-service.php:xarGenerateAiReview:response',message:'Generate AI Insights AJAX response',data:{success:!!(json&&json.success),message:(json&&json.message)||'',hasAgentDebug:!!dbg,agentDebug:dbg||null},timestamp:Date.now()})}).catch(function(){});
        // #endregion
        return json;
    });
};

window.xarSaveLeadershipContext = function (context) {
    if (!window.XFARP_WIZARD || !window.XFARP_WIZARD.arpId) {
        return Promise.reject(new Error('No ARP selected.'));
    }
    var payload = new URLSearchParams();
    payload.set('action', 'xfarp_ai_review_save_context');
    payload.set('nonce', window.XFARP_WIZARD.nonce);
    payload.set('arp_id', String(window.XFARP_WIZARD.arpId));
    payload.set('leadership_context', context || '');

    return fetch(window.XFARP_WIZARD.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); });
};
JS;
}
