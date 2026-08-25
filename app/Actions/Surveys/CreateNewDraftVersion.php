<?php

namespace App\Actions\Surveys;

use App\Enums\SurveyVersionStatus;
use App\Models\Survey;
use App\Models\SurveyVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Clones the currently-published version's full tree (sections, questions,
 * options, rules) into a brand-new draft version, so an Org Admin can keep
 * editing without touching the live, response-collecting version — the
 * "clone-on-publish" half of the edit-in-place-on-draft workflow (spec
 * §6.2).
 *
 * Rules reference question_id/section_id in their conditions/target JSON,
 * and every question/section gets a new id in the clone — so cloning
 * builds an old-id => new-id map while walking the tree, then rewrites
 * each cloned rule's conditions/target through it. A rule that somehow
 * points at an id outside that map (shouldn't happen; conditions are
 * validated against the source version's own question ids at write time)
 * is dropped rather than cloned with a dangling reference.
 */
class CreateNewDraftVersion
{
    /** @var array<int, int> old question_id => new question_id */
    private array $questionIdMap = [];

    /** @var array<int, int> old section_id => new section_id */
    private array $sectionIdMap = [];

    public function handle(Survey $survey): SurveyVersion
    {
        if ($survey->currentDraftVersion) {
            throw ValidationException::withMessages([
                'survey' => 'This survey already has a draft version in progress.',
            ]);
        }

        $source = $survey->currentPublishedVersion;

        if (! $source) {
            throw ValidationException::withMessages([
                'survey' => 'This survey has no published version to branch a new draft from.',
            ]);
        }

        return DB::transaction(function () use ($survey, $source) {
            $this->questionIdMap = [];
            $this->sectionIdMap = [];

            $draft = SurveyVersion::create([
                'survey_id' => $survey->id,
                'version_number' => $survey->versions()->max('version_number') + 1,
                'status' => SurveyVersionStatus::Draft,
                'settings' => $source->settings,
            ]);

            $this->cloneSections($source, $draft);
            $this->cloneRules($source, $draft);

            $survey->setCurrentDraftVersion($draft);

            return $draft;
        });
    }

    private function cloneSections(SurveyVersion $source, SurveyVersion $draft): void
    {
        foreach ($source->sections as $sourceSection) {
            $section = $draft->sections()->create([
                'title' => $sourceSection->title,
                'description' => $sourceSection->description,
                'order' => $sourceSection->order,
            ]);

            $this->sectionIdMap[$sourceSection->id] = $section->id;

            foreach ($sourceSection->questions as $sourceQuestion) {
                $question = $section->questions()->create([
                    'type' => $sourceQuestion->type,
                    'label' => $sourceQuestion->label,
                    'help_text' => $sourceQuestion->help_text,
                    'config' => $sourceQuestion->config,
                    'is_required' => $sourceQuestion->is_required,
                    'order' => $sourceQuestion->order,
                ]);

                $this->questionIdMap[$sourceQuestion->id] = $question->id;

                foreach ($sourceQuestion->options as $sourceOption) {
                    $question->options()->create([
                        'label' => $sourceOption->label,
                        'value' => $sourceOption->value,
                        'image_path' => $sourceOption->image_path,
                        'order' => $sourceOption->order,
                    ]);
                }
            }
        }
    }

    private function cloneRules(SurveyVersion $source, SurveyVersion $draft): void
    {
        foreach ($source->rules as $sourceRule) {
            $conditions = $this->remapConditionTree($sourceRule->conditions);
            $target = $this->remapTarget($sourceRule->target);

            if ($conditions === null || ($sourceRule->target && $target === null)) {
                continue;
            }

            $draft->rules()->create([
                'name' => $sourceRule->name,
                'conditions' => $conditions,
                'action' => $sourceRule->action,
                'target' => $target,
                'order' => $sourceRule->order,
                'is_active' => $sourceRule->is_active,
            ]);
        }
    }

    /**
     * Returns null if any leaf in the tree references a question id
     * outside $this->questionIdMap — the caller drops the whole rule
     * rather than clone it with a dangling reference.
     */
    private function remapConditionTree(mixed $node): ?array
    {
        if (! is_array($node)) {
            return null;
        }

        if (array_key_exists('conditions', $node)) {
            $children = [];

            foreach ($node['conditions'] as $child) {
                $remapped = $this->remapConditionTree($child);

                if ($remapped === null) {
                    return null;
                }

                $children[] = $remapped;
            }

            return ['operator' => $node['operator'], 'conditions' => $children];
        }

        if (! isset($node['question_id'], $this->questionIdMap[$node['question_id']])) {
            return null;
        }

        return [...$node, 'question_id' => $this->questionIdMap[$node['question_id']]];
    }

    /**
     * @param  array{question_id?: int, section_id?: int}|null  $target
     */
    private function remapTarget(?array $target): ?array
    {
        if ($target === null) {
            return null;
        }

        if (isset($target['question_id'])) {
            return isset($this->questionIdMap[$target['question_id']])
                ? ['question_id' => $this->questionIdMap[$target['question_id']]]
                : null;
        }

        if (isset($target['section_id'])) {
            return isset($this->sectionIdMap[$target['section_id']])
                ? ['section_id' => $this->sectionIdMap[$target['section_id']]]
                : null;
        }

        return null;
    }
}
