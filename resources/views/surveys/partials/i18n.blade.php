{{-- Strings the builder's JS needs for confirm()/alert()-style messages
     and placeholder text it sets when creating new rows — everything else
     is rendered directly in Blade and never needs to reach JS. --}}
@php
    $builderI18n = [
        'untitled_section' => __('surveys.builder.untitled_section'),
        'question_label_placeholder' => __('surveys.builder.question_label_placeholder'),
        'option_label_placeholder' => __('surveys.builder.option_label_placeholder'),
        'confirm_delete_section' => __('surveys.builder.confirm_delete_section'),
        'confirm_delete_question' => __('surveys.builder.confirm_delete_question'),
        'publish_confirm' => __('surveys.builder.publish_confirm'),
        'save_failed' => __('surveys.builder.save_failed'),
        'untitled_row' => __('surveys.builder.untitled_row'),
        'untitled_column' => __('surveys.builder.untitled_column'),
    ];
@endphp
<script>
    window.__i18n = @json($builderI18n);
</script>
