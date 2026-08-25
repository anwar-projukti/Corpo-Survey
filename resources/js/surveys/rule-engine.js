/**
 * Client-side mirror of App\Support\RuleEngine — evaluates a survey
 * version's conditional logic rules against the answers collected so far,
 * to decide what the respondent sees next. This is a UX convenience only:
 * the server re-evaluates the same rules from scratch on submission
 * (App\Actions\Surveys\SubmitSurveyResponse), so nothing here needs to be
 * trusted, only kept in sync with what the server actually enforces.
 */
function isEmpty(value) {
    return value === null || value === undefined || value === '' || (Array.isArray(value) && value.length === 0);
}

function normalize(value) {
    return Array.isArray(value) ? value : String(value ?? '');
}

function conditionMet(node, answers) {
    if (!node || typeof node !== 'object') return false;

    if ('conditions' in node) {
        const results = node.conditions.map((child) => conditionMet(child, answers));
        return node.operator === 'or' ? results.includes(true) : !results.includes(false);
    }

    const actual = answers[node.question_id];
    const expected = node.value;

    switch (node.operator) {
        case 'equals':
            return normalize(actual) == normalize(expected); // eslint-disable-line eqeqeq
        case 'not_equals':
            return normalize(actual) != normalize(expected); // eslint-disable-line eqeqeq
        case 'gt':
            return isFinite(actual) && isFinite(expected) && Number(actual) > Number(expected);
        case 'gte':
            return isFinite(actual) && isFinite(expected) && Number(actual) >= Number(expected);
        case 'lt':
            return isFinite(actual) && isFinite(expected) && Number(actual) < Number(expected);
        case 'lte':
            return isFinite(actual) && isFinite(expected) && Number(actual) <= Number(expected);
        case 'contains':
            return Array.isArray(actual) ? actual.includes(expected) : String(actual ?? '').includes(String(expected ?? ''));
        case 'is_answered':
            return !isEmpty(actual);
        case 'is_not_answered':
            return isEmpty(actual);
        default:
            return false;
    }
}

export default class RuleEngine {
    constructor(sections, rules, answers) {
        this.sections = sections;
        this.rules = rules || [];
        this.answers = answers;
    }

    activeRules() {
        return this.rules.filter((rule) => rule.is_active && conditionMet(rule.conditions, this.answers));
    }

    skippedSectionIds() {
        return this.activeRules()
            .filter((rule) => rule.action === 'skip')
            .map((rule) => rule.target.section_id);
    }

    hiddenQuestionIds() {
        const skippedSectionIds = this.skippedSectionIds();
        const hidden = new Set();

        for (const section of this.sections) {
            if (skippedSectionIds.includes(section.id)) {
                section.questions.forEach((q) => hidden.add(q.id));
            }
        }

        for (const rule of this.activeRules()) {
            if (rule.action === 'hide') hidden.add(rule.target.question_id);
        }

        return hidden;
    }

    requiredQuestionIds() {
        const hidden = this.hiddenQuestionIds();
        const required = new Set();

        for (const section of this.sections) {
            for (const question of section.questions) {
                if (question.is_required && !hidden.has(question.id)) required.add(question.id);
            }
        }

        for (const rule of this.activeRules()) {
            if (rule.action === 'require') required.add(rule.target.question_id);
            if (rule.action === 'make_optional') required.delete(rule.target.question_id);
        }

        return required;
    }

    shouldEndSurvey() {
        return this.activeRules().some((rule) => rule.action === 'end_survey');
    }

    /** Questions visible to the respondent right now, in section/question order. */
    visibleQuestions() {
        const hidden = this.hiddenQuestionIds();

        return this.sections.flatMap((section) => section.questions.filter((q) => !hidden.has(q.id)));
    }
}
