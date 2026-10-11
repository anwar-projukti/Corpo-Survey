import './bootstrap';
import Alpine from 'alpinejs';
import themeStore from './stores/theme';
import localeSwitcher from './locale-switcher';
import publicSurveyForm from './surveys/public-form';

/**
 * A dedicated, minimal entry point for the respondent-facing public
 * survey page (spec §3.2/§16's mobile-first requirement) — the single
 * app.js bundle used by the whole admin dashboard (survey builder,
 * analytics/Chart.js, import/export wizards, ...) is unrelated code
 * weight for an anonymous respondent on a phone. Only the
 * theme store (the layout's theme switcher needs it) and the public form
 * component are registered here.
 */
window.Alpine = Alpine;

Alpine.store('theme', themeStore());
Alpine.data('localeSwitcher', localeSwitcher);
Alpine.data('publicSurveyForm', publicSurveyForm);

Alpine.start();
