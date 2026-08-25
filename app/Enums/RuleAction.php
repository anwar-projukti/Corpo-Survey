<?php

namespace App\Enums;

/**
 * Conditional logic actions from spec §6.3.
 */
enum RuleAction: string
{
    case Show = 'show';
    case Hide = 'hide';
    case Skip = 'skip';
    case GoToSection = 'go_to_section';
    case EndSurvey = 'end_survey';
    case Require = 'require';
    case MakeOptional = 'make_optional';

    /**
     * Whether this action needs a target (a question_id or section_id to
     * act on) — end_survey doesn't target anything.
     */
    public function requiresTarget(): bool
    {
        return $this !== self::EndSurvey;
    }

    /**
     * 'question' | 'section' — which kind of id `target` should carry.
     */
    public function targetType(): ?string
    {
        return match ($this) {
            self::Show, self::Hide, self::Require, self::MakeOptional => 'question',
            self::Skip, self::GoToSection => 'section',
            self::EndSurvey => null,
        };
    }
}
