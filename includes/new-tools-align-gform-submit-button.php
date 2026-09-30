<?php

/**
 * Just a css for new tools page to align submit button.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_head', function () {

    $allowed_pages = [
        'situational-leadership-scenarios-tactics-exercise-new',
        'leadership-impact-legacy-roadmap-new',
        'burnout-inventory-tool-for-leaders-new',
        'conflict-resolution-tool-new',
        'communication-tracker-new',
        'psychological-safety-inventory-new',
        'performance-improvement-plan-case-studies-new',
        'p-e-s-t-situational-analysis-tool-new',
        'sop-building-tool-new',
        'raci-accountability-matrix-new',
        'start-stop-continue-exercise-new',
        'rubric-for-assessing-meeting-effectiveness-new',
        'q20-new',
        'gpi-assessment-new',

        'self-awareness-and-our-performance-narrative-new',
        'self-reflective-awareness-checklist-new',
        'fear-and-vulnerability-exercise-new',
        'emotional-comfort-zone-new',
        'cognitive-checkin-exercises-new',
        'abc-worksheet-new',

        'individual-performance-plan-template-new',
        'work-identification-prioritization-new',
        'smart-goal-worksheet-new',
        'development-vs-performance-goal-setting-activity-new',
        'accomplishment-tracking-new',
        'saying-no-scripts-worksheet-new',

        'fill-buckets-new',
        'meaningful-relationships-activity-new',
        'social-media-detox-new',

        'problem-solving-template-new',
        'swot-analysis-new',

        'igniting-my-purpose-new',
        'legacy-roadmap-new',
        'mattering-swot-new',
    ];

    if (is_page($allowed_pages)) {
        echo '<style>
            input.gform_button {
                top: 113px !important;
            }
        </style>';
    }
});
