<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Html;

use PDO;

final class OverviewProvider extends AbstractHtml
{
    /** @throws void */
    public function __construct(PDO $pdo, private readonly array $provider, string | null $title = null)
    {
        parent::__construct($pdo, $title);
    }

    /** @throws void */
    public function getHtml(): string
    {
        $body = '
<div class="section">
    <h1 class="header center orange-text">' . $this->provider['proName'] . ' overview - <small>' . $this->provider['proVersion'] . '</small></h1>

    <div class="row center">
        <h5 class="header light">
            We took <strong>' . $this->getUserAgentCount() . '</strong> different user agents and analyzed them with this provider<br />
        </h5>
    </div>
</div>

<div class="section">
    ' . $this->getTable() . '
</div>
';

        return parent::getHtmlCombined($body);
    }

    /**
     * @return array<string, int>
     *
     * @throws void
     */
    private function getResult(): array
    {
        $sql = '
            SELECT
                SUM(`resResultFound`) AS `resultFound`,

                COUNT(`resBrowserName`) AS `browserFound`,
                COUNT(`resEngineName`) AS `engineFound`,
                COUNT(`resOsName`) AS `osFound`,

                COUNT(`resDeviceModel`) AS `deviceModelFound`,
                COUNT(`resDeviceBrand`) as `deviceBrandFound`,
                COUNT(`resDeviceType`) AS `deviceTypeFound`,
                COUNT(`resDeviceIsMobile`) AS `asMobileDetected`,

                COUNT(`resBotIsBot`) AS `asBotDetected`,
                COUNT(`resBotName`) AS `botNameFound`,
                COUNT(`resBotType`) AS `botTypeFound`,

                AVG(`resParseTime`) AS `avgParseTime`
            FROM `result`
            INNER JOIN `real-provider` ON `proId` = `provider_id`
            WHERE
                `provider_id` = :proId
            GROUP BY
                `proId`
        ';

        $statement = $this->pdo->prepare($sql);

        $statement->bindValue(':proId', $this->provider['proId'], PDO::PARAM_STR);

        $statement->execute();

        $result = $statement->fetch();

        if ($result === false) {
            $result = [
                'asBotDetected' => 0,
                'asMobileDetected' => 0,
                'botNameFound' => 0,
                'botTypeFound' => 0,
                'browserFound' => 0,
                'deviceBrandFound' => 0,
                'deviceModelFound' => 0,
                'deviceTypeFound' => 0,
                'engineFound' => 0,
                'osFound' => 0,
                'resultFound' => 0,
            ];
        }

        return $result;
    }

    /** @throws void */
    private function getTable(): string
    {
        $provider = $this->provider;

        $html = '<table class="striped">';

        /*
         * Header
         */
        $html .= '
            <thead>
            <tr>
                <th>
                    Group
                </th>
                <th>
                    Percent
                </th>
                <th>
                    Total
                </th>
            </tr>
            </thead>
        ';

        /*
         * body
         */
        $row = $this->getResult();

        $html .= '<tbody>';

        /*
         * Results found
         */
        $html .= '
            <tr>
            <td>
                Results found
            </td>
            <td>
                ' . $this->getPercentageMarkup($row['resultFound']) . '
            </td>
            <td>
                ' . $row['resultFound'] . '
            </td>
            </tr>
        ';

        /*
         * browser
         */
        if ($provider['proCanDetectBrowserName']) {
            $html .= '
                <tr>
                <td>
                    Browser names
                </td>
                <td>
                    ' . $this->getPercentageMarkup($row['browserFound']) . '
                </td>
                <td>
                    ' . $row['browserFound'] . '
                </td>
                </tr>
            ';
        } else {
            $html .= '
                <tr>
                <td>
                    Engine names
                </td>
                <td colspan="2" class="center-align red lighten-1">
                    <strong>Not available with this provider</strong>
                </td>
                </tr>
            ';
        }

        /*
         * engine
         */
        if ($provider['proCanDetectEngineName']) {
            $html .= '
                <tr>
                <td>
                    Rendering engines
                </td>
                <td>
                    ' . $this->getPercentageMarkup($row['engineFound']) . '
                </td>
                <td>
                    ' . $row['engineFound'] . '
                </td>
                </tr>
            ';
        } else {
            $html .= '
                <tr>
                <td>
                    Engine name
                </td>
                <td colspan="2" class="center-align red lighten-1">
                    <strong>Not available with this provider</strong>
                </td>
                </tr>
            ';
        }

        /*
         * os
         */
        if ($provider['proCanDetectOsName']) {
            $html .= '
                <tr>
                <td>
                    Operating systems
                </td>
                <td>
                    ' . $this->getPercentageMarkup($row['osFound']) . '
                </td>
                <td>
                    ' . $row['osFound'] . '
                </td>
                </tr>
            ';
        } else {
            $html .= '
                <tr>
                <td>
                    Operating systems
                </td>
                <td colspan="2" class="center-align red lighten-1">
                    <strong>Not available with this provider</strong>
                </td>
                </tr>
            ';
        }

        /*
         * device brand
         */
        if ($provider['proCanDetectDeviceBrand']) {
            $html .= '
                <tr>
                <td>
                    Device brands
                </td>
                <td>
                    ' . $this->getPercentageMarkup($row['deviceBrandFound']) . '
                </td>
                <td>
                    ' . $row['deviceBrandFound'] . '
                </td>
                </tr>
            ';
        } else {
            $html .= '
                <tr>
                <td>
                    Device brands
                </td>
                <td colspan="2" class="center-align red lighten-1">
                    <strong>Not available with this provider</strong>
                </td>
                </tr>
            ';
        }

        /*
         * device model
         */
        if ($provider['proCanDetectDeviceModel']) {
            $html .= '
                <tr>
                <td>
                    Device models
                </td>
                <td>
                    ' . $this->getPercentageMarkup($row['deviceModelFound']) . '
                </td>
                <td>
                    ' . $row['deviceModelFound'] . '
                </td>
                </tr>
            ';
        } else {
            $html .= '
                <tr>
                <td>
                    Device models
                </td>
                <td colspan="2" class="center-align red lighten-1">
                    <strong>Not available with this provider</strong>
                </td>
                </tr>
            ';
        }

        /*
         * device type
         */
        if ($provider['proCanDetectDeviceType']) {
            $html .= '
                <tr>
                <td>
                    Device types
                </td>
                <td>
                    ' . $this->getPercentageMarkup($row['deviceTypeFound']) . '
                </td>
                <td>
                    ' . $row['deviceTypeFound'] . '
                </td>
                </tr>
            ';
        } else {
            $html .= '
                <tr>
                <td>
                    Device types
                </td>
                <td colspan="2" class="center-align red lighten-1">
                    <strong>Not available with this provider</strong>
                </td>
                </tr>
            ';
        }

        /*
         * Is mobile
         */
        if ($provider['proCanDetectDeviceIsMobile']) {
            $html .= '
                <tr>
                <td>
                    Is mobile
                </td>
                <td>
                    ' . $this->getPercentageMarkup($row['asMobileDetected']) . '
                </td>
                <td>
                    ' . $row['asMobileDetected'] . '
                </td>
                </tr>
            ';
        } else {
            $html .= '
                <tr>
                <td>
                    Is mobile
                </td>
                <td colspan="2" class="center-align red lighten-1">
                    <strong>Not available with this provider</strong>
                </td>
                </tr>
            ';
        }

        /*
         * Is bot
         */
        if ($provider['proCanDetectBotIsBot']) {
            $html .= '
                <tr>
                <td>
                    Is bot
                </td>
                <td>
                    ' . $this->getPercentageMarkup($row['asBotDetected']) . '
                </td>
                <td>
                    ' . $row['asBotDetected'] . '
                </td>
                </tr>
            ';
        } else {
            $html .= '
                <tr>
                <td>
                    Is bot
                </td>
                <td colspan="2" class="center-align red lighten-1">
                    <strong>Not available with this provider</strong>
                </td>
                </tr>
            ';
        }

        /*
         * Bot name
         */
        if ($provider['proCanDetectBotName']) {
            $html .= '
                <tr>
                <td>
                    Bot names
                </td>
                <td>
                    ' . $this->getPercentageMarkup($row['botNameFound']) . '
                </td>
                <td>
                    ' . $row['botNameFound'] . '
                </td>
                </tr>
            ';
        } else {
            $html .= '
                <tr>
                <td>
                    Bot names
                </td>
                <td colspan="2" class="center-align red lighten-1">
                    <strong>Not available with this provider</strong>
                </td>
                </tr>
            ';
        }

        /*
         * Bot type
         */
        if ($provider['proCanDetectBotType']) {
            $html .= '
                <tr>
                <td>
                    Bot types
                </td>
                <td>
                    ' . $this->getPercentageMarkup($row['botTypeFound']) . '
                </td>
                <td>
                    ' . $row['botTypeFound'] . '
                </td>
                </tr>
            ';
        } else {
            $html .= '
                <tr>
                <td>
                    Bot types
                </td>
                <td colspan="2" class="center-align red lighten-1">
                    <strong>Not available with this provider</strong>
                </td>
                </tr>
            ';
        }

        $html .= '</tbody>';

        return $html . '</table>';
    }
}
