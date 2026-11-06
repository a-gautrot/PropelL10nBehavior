<?php

namespace Gautrot\Propel\Behavior\L10n;

use LogicException;

/**
 * # RenderTrait
 */
trait RenderTrait
{
    /**
     * @return L10nBehavior
     */
    protected function getL10nBehavior(): L10nBehavior
    {
        if (!$this->behavior instanceof L10nBehavior) {
            throw new LogicException('Localization generation requires an L10n behavior.');
        }

        return $this->behavior;
    }

    /**
     * @param string $name
     * @param array<string, mixed> $vars
     * @return string
     */
    protected function renderTemplate(string $name, array $vars = []): string
    {
        $vars['localeVariableName'] = $this->getL10nBehavior()->getLocaleVariableName();
        $this->getL10nBehavior()->backupTemplatesDirname();

        try {
            return $this->getL10nBehavior()->renderTemplate($name, $vars);
        } finally {
            $this->getL10nBehavior()->restoreTemplatesDirname();
        }
    }
}
