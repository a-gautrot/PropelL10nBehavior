<?php

namespace Gautrot\Propel\Behavior\L10n;

use Locale;
use InvalidArgumentException;

/**
 * # PropelL10n
 * @api
 */
class PropelL10n
{
    /**
     * @var array<string, string>
     */
    private static array $dependencies = [];

    /**
     * @var string
     */
    private static string $locale = 'en';

    /**
     * @var string
     */
    private static string $fallback = 'en';

    /**
     * Returns the fallback locale
     *
     * @return string
     */
    public static function getFallback(): string
    {
        return self::$fallback;
    }

    /**
     * Sets the fallback locale
     *
     * @param string $locale
     * @return void
     */
    public static function setFallback(string $locale): void
    {
        self::$fallback = PropelL10n::normalize($locale);
    }

    /**
     * @param string $locale
     * @return string
     */
    public static function normalize(string $locale): string
    {
        $parts = Locale::parseLocale($locale);

        if ($parts === null || !isset($parts['language'])) {
            throw new InvalidArgumentException('The locale must contain a language.');
        }

        $normalized = Locale::composeLocale($parts);

        if ($normalized === false) {
            throw new InvalidArgumentException('The locale cannot be normalized.');
        }

        return str_replace('_', '-', $normalized);
    }

    /**
     * Gets the current locale
     *
     * @return string
     */
    public static function getLocale(): string
    {
        return self::$locale;
    }

    /**
     * Sets the current locale
     *
     * @param string $locale
     * @return void
     */
    public static function setLocale(string $locale): void
    {
        self::$locale = PropelL10n::normalize($locale);
    }

    /**
     * Removes a dependency
     *
     * @param string $locale
     * @return void
     */
    public static function removeDependency(string $locale): void
    {
        unset(self::$dependencies[PropelL10n::normalize($locale)]);
    }

    /**
     * Returns the current dependencies
     *
     * @return array<string, string>
     */
    public static function getDependencies(): array
    {
        return self::$dependencies;
    }

    /**
     * Sets multiple dependencies at once. The array must be in the following format:
     *
     * `['de-DE' => 'en-US', 'de-CH' => 'de-DE']`
     *
     * which means de-DE depends on en-US and de-CH depends on de-DE.
     *
     * @param array<string, string> $dependencies
     * @return void
     */
    public static function setDependencies(array $dependencies): void
    {
        self::$dependencies = [];

        foreach ($dependencies as $k => $v) {
            self::addDependency($k, $v);
        }
    }

    /**
     * Adds a dependency
     *
     * @param string $locale the new locale which has a dependency
     * @param string $dependsOn the locale on which it depends on
     * @return void
     */
    public static function addDependency(string $locale, string $dependsOn): void
    {
        $locale = self::normalize($locale);
        $dependsOn = self::normalize($dependsOn);
        self::$dependencies[$locale] = $dependsOn;
    }

    /**
     * Returns the dependency for the given locale or null if locale doesn't exist
     *
     * @param string $locale
     * @return string|null
     */
    public static function getDependency(string $locale): ?string
    {
        $locale = PropelL10n::normalize($locale);

        return self::$dependencies[$locale] ?? null;
    }

    /**
     * Checks whether there is a dependency registered for the given locale
     *
     * @param string $locale
     * @return bool
     */
    public static function hasDependency(string $locale): bool
    {
        $locale = PropelL10n::normalize($locale);

        return isset(self::$dependencies[$locale]);
    }

    /**
     * @param string $locale
     * @return string[]
     */
    public static function getLocaleChain(string $locale): array
    {
        $locales = [$locale];
        $listLocales = [];

        // Get the locale set in PropelL10N
        $dependency = self::getDependency($locale);

        // Loop to save any variants of a language set from $locale (ex: 'de-CH', 'de-DE', etc.)
        while ($dependency !== null && !isset($listLocales[$locale])) {
            $listLocales[$locale] = true;

            if (isset($listLocales[$dependency])) {
                break;
            }

            if (Locale::getPrimaryLanguage($dependency) !== Locale::getPrimaryLanguage($locale)) {
                self::addParentLocales($locales, $locale);
            }

            $locale = $dependency;
            $locales[] = $locale;
            $dependency = self::getDependency($locale);
        }

        // Adds the general ISO language (ex: keeps 'de' from 'de-DE')
        self::addParentLocales($locales, $locale);

        // Adds 'en' as the last language available in case of
        $locales[] = self::getFallback();

        return array_values(array_unique($locales));
    }

    /**
     * @param string[] $locales
     * @param string $locale
     * @return void
     */
    private static function addParentLocales(array &$locales, string $locale): void
    {
        $parts = explode('-', $locale);

        if (count($parts) > 1) {
            array_pop($parts);
            $locales[] = implode('-', $parts);
        }
    }

    /**
     * Counts the dependencies a locale may have.
     *
     * E.g. given these dependencies:
     * `['de-DE' => 'en-US', 'de-CH' => 'de-DE']`
     *
     * Then de-CH has 2 dependencies in total.
     *
     * @param string $locale
     * @return int
     */
    public static function countDependencies(string $locale): int
    {
        $locale = PropelL10n::normalize($locale);
        $count = 0;
        $locales = [];

        while (isset(self::$dependencies[$locale]) && !isset($locales[$locale])) {
            $locales[$locale] = true;
            $locale = self::$dependencies[$locale];
            $count++;
        }

        return $count;
    }
}
