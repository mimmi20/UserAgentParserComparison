<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Html;

use PDO;

use function extension_loaded;
use function htmlspecialchars;
use function number_format;
use function round;
use function zend_version;

use const PHP_OS;
use const PHP_VERSION;

final class OverviewGeneral extends AbstractHtml
{
    /** @throws void */
    public function getHtml(): string
    {
        $body = '
<div class="section">
    <h1 class="header center orange-text">Useragent parser comparison v' . COMPARISON_VERSION . '</h1>

    <div class="row center">
        <h5 class="header light">
            We took <strong>' . number_format($this->getUserAgentCount()) . '</strong> different user agents and analyzed them with all providers below.<br />
            That way, it\'s possible to get a good overview of each provider
        </h5>
    </div>
</div>

<div class="section">
    <h3 class="header center orange-text">
        Detected by all providers
    </h3>

    ' . $this->getTableSummary() . '

</div>

<div class="section">
    <h3 class="header center orange-text">
        Sources of the user agents
    </h3>
    <div class="row center">
        <h5 class="header light">
            The user agents were extracted from different test suites when possible<br />
            <strong>Note</strong> The actual number of tested user agents can be higher in the test suite itself.
        </h5>
    </div>

    ' . $this->getTableTests() . '

</div>
';

        return parent::getHtmlCombined($body);
    }

    /**
     * @return iterable<array<string, mixed>>
     *
     * @throws void
     */
    private function getProviders(): iterable
    {
        $statement = $this->pdo->prepare('SELECT * FROM `providers-general-overview`');

        $statement->execute();

        yield from $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return iterable<array<string, mixed>>
     *
     * @throws void
     */
    private function getUserAgentPerProviderCount(): iterable
    {
        $statement = $this->pdo->prepare('SELECT * FROM `useragents-general-overview`');

        $statement->execute();

        yield from $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @throws void */
    private function getTableSummary(): string
    {
        $html = '<table class="striped">';

        /*
         * Header
         */
        $html .= '
            <thead>
                <tr>
                    <th>
                        Provider
                    </th>
                    <th>
                        Results
                    </th>
                    <th>
                        Browser
                    </th>
                   <th>
                        Engine
                    </th>
                   <th>
                        Operating system
                    </th>
                   <th>
                        Device brand
                    </th>
                    <th>
                        Device model
                    </th>
                    <th>
                        Device type
                    </th>
                   <th>
                        Is mobile
                    </th>
                   <th>
                        Is bot
                    </th>
                   <th>
                        Parse time
                    </th>
                    <th>
                        Actions
                    </th>
                </tr>
            </thead>
        ';

        /*
         * body
         */
        $html .= '<tbody>';

        foreach ($this->getProviders() as $row) {
            $html .= '<tr>';

            $html .= '<th>';

            if ('' !== $row['proPackageName']) {
                $html .= '<a href="https://packagist.org/packages/' . $row['proPackageName'] . '">' . $row['proName'] . '</a>';
                $html .= '<br /><small>' . $row['proVersion'] . '</small>';
                $html .= '<br /><small>' . $row['proLastReleaseDate'] . '</small>';
            } else {
                $html .= '<a href="' . $row['proHomepage'] . '">' . $row['proName'] . '</a>';
                $html .= '<br /><small>Cloud API</small>';
            }

            $html .= '</th>';

            /*
             * Result found?
             */
            $html .= '<td>' . $this->getPercentageMarkup((int) $row['resultFound']) . '</td>';

            /*
             * Browser
             */
            if ($row['proCanDetectBrowserName']) {
                $html .= '<td>' . $this->getPercentageMarkup((int) $row['browserFound']) . '</td>';
            } else {
                $html .= '<td>&nbsp;</td>';
            }

            /*
             * Engine
             */
            if ($row['proCanDetectEngineName']) {
                $html .= '<td>' . $this->getPercentageMarkup((int) $row['engineFound']) . '</td>';
            } else {
                $html .= '<td>&nbsp;</td>';
            }

            /*
             * OS
             */
            if ($row['proCanDetectOsName']) {
                $html .= '<td>' . $this->getPercentageMarkup((int) $row['osFound']) . '</td>';
            } else {
                $html .= '<td>&nbsp;</td>';
            }

            /*
             * device
             */
            if ($row['proCanDetectDeviceBrand']) {
                $html .= '<td>' . $this->getPercentageMarkup((int) $row['deviceBrandFound']) . '</td>';
            } else {
                $html .= '<td>&nbsp;</td>';
            }

            if ($row['proCanDetectDeviceModel']) {
                $html .= '<td>' . $this->getPercentageMarkup((int) $row['deviceModelFound']) . '</td>';
            } else {
                $html .= '<td>&nbsp;</td>';
            }

            if ($row['proCanDetectDeviceType']) {
                $html .= '<td>' . $this->getPercentageMarkup((int) $row['deviceTypeFound']) . '</td>';
            } else {
                $html .= '<td>&nbsp;</td>';
            }

            if ($row['proCanDetectDeviceIsMobile']) {
                $html .= '<td>' . $this->getPercentageMarkup((int) $row['asMobileDetected']) . '</td>';
            } else {
                $html .= '<td>&nbsp;</td>';
            }

            if ($row['proCanDetectBotIsBot']) {
                $html .= '<td>' . $this->getPercentageMarkup((int) $row['asBotDetected']) . '</td>';
            } else {
                $html .= '<td>&nbsp;</td>';
            }

            $info = 'PHP v' . PHP_VERSION . ' | Zend v' . zend_version() . ' | On ' . PHP_OS;

            if (extension_loaded('xdebug')) {
                $info .= ' | with xdebug';
            }

            if (extension_loaded('zend opcache')) {
                $info .= ' | with opcache';
            }

            $html .= '
                <td>
                    <a class="tooltipped" data-position="top" data-delay="50" data-tooltip="' . htmlspecialchars($info) . '">
                        ' . round((float) $row['avgParseTime'], 5) . '
                    </a>
                </td>
            ';

            $html .= '<td><a href="' . $row['proName'] . '.html" class="btn waves-effect waves-light">Details</a></td>';

            $html .= '</tr>';
        }

        $html .= '</tbody>';

        $html .= '</table>';

        return $html;
    }

    /** @throws void */
    private function getTableTests(): string
    {
        $html = '<table class="striped">';

        /*
         * Header
         */
        $html .= '
            <thead>
            <tr>
                <th>
                    Provider
                </th>
                <th class="right-align">
                    Number of user agents
                </th>
            </tr>
            </thead>
        ';

        /*
         * Body
         */
        $html .= '<tbody>';

        foreach ($this->getUserAgentPerProviderCount() as $row) {
            $html .= '<tr>';

            $html .= '<td>' . $row['proName'] . '</td>';
            $html .= '<td class="right-align">' . number_format($row['countNumber']) . '</td>';

            $html .= '</tr>';
        }

        $html .= '</tbody>';

        $html .= '</table>';

        return $html;
    }
}
