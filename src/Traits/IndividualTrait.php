<?php

/**
 * See LICENSE.md file for further details.
 */

declare(strict_types=1);

namespace MagicSunday\Webtrees\FanChart\Traits;

use DOMDocument;
use DOMNode;
use DOMXPath;
use Fisharebest\Webtrees\Family;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;

/**
 * Trait IndividualTrait.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link    https://github.com/magicsunday/webtrees-fan-chart/
 */
trait IndividualTrait
{
    /**
     * The XPath identifiers to extract the name parts.
     */
    private $xpathFirstNames      = '//text()[following::span[@class="SURN"]][normalize-space()]';
    private $xpathLastNames       = '//text()[parent::*[not(@class="wt-nickname")]][not(following::span[@class="SURN"])][normalize-space()]';
    private $xpathNickname        = '//q[@class="wt-nickname"]';
    private $xpathPreferredName   = '//span[@class="starredname"]';
    private $xpathAlternativeName = '//span[contains(attribute::class, "NAME")]';

    /**
     * Get the individual data required for display the chart.
     *
     * @param Individual $individual The current individual
     * @param int        $generation The generation the person belongs to
     *
     * @return string[][]
     */
    private function getIndividualData(Individual $individual, int $generation): array
    {
        $primaryName = $individual->getAllNames()[$individual->getPrimaryName()];

        // The formatted name of the individual (containing HTML)
        $full = $primaryName['full'];

        // Get xpath
        $xpath = $this->getXPath($full);

        // The name of the person without formatting of the individual parts of the name.
        // Remove placeholders as we do not need them in this module
        $fullNN = str_replace(['@N.N.', '@P.N.'], '', $primaryName['fullNN']);

        // Extract name parts (Do not change processing order!)
        $preferredName    = $this->getPreferredName($xpath);
        $lastNames        = $this->getLastNames($xpath);
        $firstNames       = $this->getFirstNames($xpath);
        $alternativeNames = $this->getAlternateNames($individual);

        return [
            'id'               => 0,
            'xref'             => $individual->xref(),
            'url'              => $individual->url(),
            'updateUrl'        => $this->getUpdateRoute($individual),
            'generation'       => $generation,
            'name'             => $fullNN,
            'firstNames'       => $firstNames,
            'lastNames'        => $lastNames,
            'preferredName'    => $preferredName,
            'alternativeNames' => $alternativeNames,
            'isAltRtl'         => $this->isRtl($alternativeNames),
            'sex'              => $individual->sex(),
            'timespan'         => $this->getLifetimeDescription($individual),
            'marriage'         => $this->getParentMarriageDate($individual),
            'color'            => $this->getColor($individual),
            'colors'           => [[], []],
        ];
    }

    /**
     * Returns the DOMXPath instance.
     *
     * @param string $fullName The individuals full name (containing HTML)
     *
     * @return DOMXPath
     */
    private function getXPath(string $fullName): DOMXPath
    {
        $document = new DOMDocument();
        $document->loadHTML(mb_convert_encoding($fullName, 'HTML-ENTITIES', 'UTF-8'));

        return new DOMXPath($document);
    }

    /**
     * Create the timespan label.
     *
     * @param Individual $individual The current individual
     *
     * @return string
     */
    private function getLifetimeDescription(Individual $individual): string
    {
        if ($individual->getBirthDate()->isOK() && $individual->getDeathDate()->isOK()) {
            return $individual->getBirthYear() . '-' . $individual->getDeathYear();
        }

        if ($individual->getBirthDate()->isOK()) {
            return I18N::translate('Born: %s', $individual->getBirthYear());
        }

        if ($individual->getDeathDate()->isOK()) {
            return I18N::translate('Died: %s', $individual->getDeathYear());
        }

        if ($individual->isDead()) {
            return I18N::translate('Deceased');
        }

        return '';
    }

    private function getParentMarriageDate(Individual $individual): string
    {
        /** @var Family $family */
        $family = $individual->childFamilies()->first();

        if ($family) {
            return strip_tags($family->getMarriageDate()->display());
        }

        return '';
    }

    /**
     * Returns all first names from the given full name.
     *
     * @param DOMXPath $xpath The DOMXPath instance used to parse for the preferred name.
     *
     * @return string[]
     */
    public function getFirstNames(DOMXPath $xpath): array
    {
        $nodeList   = $xpath->query($this->xpathFirstNames);
        $firstNames = [];

        /** @var DOMNode $node */
        foreach ($nodeList as $node) {
            $firstNames[] = trim($node->nodeValue);
        }

        $firstNames = explode(' ', implode(' ', $firstNames));

        return array_values(array_filter($firstNames));
    }

    /**
     * Returns all last names from the given full name.
     *
     * @param DOMXPath $xpath The DOMXPath instance used to parse for the preferred name.
     *
     * @return string[]
     */
    public function getLastNames(DOMXPath $xpath): array
    {
        $nodeList  = $xpath->query($this->xpathLastNames);
        $lastNames = [];

        /** @var DOMNode $node */
        foreach ($nodeList as $node) {
            $lastNames[] = trim($node->nodeValue);
        }

        // Concat to full last name (as SURN may contain a prefix and a separate suffix)
        $lastNames = explode(' ', implode(' ', $lastNames));

        return array_values(array_filter($lastNames));
    }

    /**
     * Returns the preferred name from the given full name.
     *
     * @param DOMXPath $xpath The DOMXPath instance used to parse for the preferred name.
     *
     * @return string
     */
    public function getPreferredName(DOMXPath $xpath): string
    {
        $nodeList = $xpath->query($this->xpathPreferredName);
        return $nodeList->length ? $nodeList->item(0)->nodeValue : '';
    }

    /**
     * Returns the preferred name from the given full name.
     *
     * @param Individual $individual
     *
     * @return string[]
     */
    public function getAlternateNames(Individual $individual): array
    {
        $name = $individual->alternateName();

        if ($name === null) {
            return [];
        }

        $xpath    = $this->getXPath($name);
        $nodeList = $xpath->query($this->xpathAlternativeName);
        $name     = $nodeList->length ? $nodeList->item(0)->nodeValue : '';

        return array_filter(explode(' ', $name));
    }
}
