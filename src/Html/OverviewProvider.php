<?php
namespace UserAgentParserComparison\Html;

class OverviewProvider extends AbstractHtml
{

    private array $provider;

    public function __construct(\PDO $pdo, array $provider, ?string $title = null)
    {
        $this->pdo = $pdo;
        $this->provider = $provider;
        $this->title = $title;
    }

    private function getResult(): array
    {
        $sql = "
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
            INNER JOIN `provider` ON `proId` = `provider_id`
            WHERE
                `provider_id` = :proId
            GROUP BY
                `proId`
        ";

        $statement = $this->pdo->prepare($sql);

        $statement->bindValue(':proId', $this->provider['proId'], \PDO::PARAM_STR);

        $statement->execute();
        
        return $statement->fetch();
    }

    private function getTable(): string
    {
        $provider = $this->provider;
        
        $html = '<table class="striped">';
        
        /*
         * Header
         */
        $html .= '
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
                <th>
                    Actions
                </th>
            </tr>
        ';
        
        /*
         * body
         */
        $totalUserAgentsOnePercent = $this->getUserAgentCount() / 100;
        
        $row = $this->getResult();
        
        $html .= '<tbdoy>';
        
        /*
         * Results found
         */
        $html .= '
            <tr>
            <td>
                Results found
            </td>
            <td>
                ' . round($row['resultFound'] / $totalUserAgentsOnePercent, 2) . '%
                <div class="progress">
                    <div class="determinate" style="width: ' . round($row['resultFound'] / $totalUserAgentsOnePercent, 0) . '"></div>
                </div>
            </td>
            <td>
                ' . $row['resultFound'] . '
            </td>
            <td>
                <a href="not-detected/' . $provider['proName'] . '/no-result-found.html" class="btn waves-effect waves-light">
                    Not detected
                </a>
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
                    ' . round($row['browserFound'] / $totalUserAgentsOnePercent, 2) . '%
                    <div class="progress">
                        <div class="determinate" style="width: ' . round($row['browserFound'] / $totalUserAgentsOnePercent, 0) . '"></div>
                    </div>
                </td>
                <td>
                    ' . $row['browserFound'] . '
                </td>
                <td>
                    <a href="detected/' . $provider['proName'] . '/browser-names.html" class="btn waves-effect waves-light">
                        Detected
                    </a>
                    <a href="not-detected/' . $provider['proName'] . '/browser-names.html" class="btn waves-effect waves-light">
                        Not detected
                    </a>
                </td>
                </tr>
            ';
        } else {
            $html .= '
                <tr>
                <td>
                    Engine names
                </td>
                <td colspan="3" class="center-align red lighten-1">
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
                    ' . round($row['engineFound'] / $totalUserAgentsOnePercent, 2) . '%
                    <div class="progress">
                        <div class="determinate" style="width: ' . round($row['engineFound'] / $totalUserAgentsOnePercent, 0) . '"></div>
                    </div>
                </td>
                <td>
                    ' . $row['engineFound'] . '
                </td>
                <td>
                    <a href="detected/' . $provider['proName'] . '/rendering-engines.html" class="btn waves-effect waves-light">
                        Detected
                    </a>
                    <a href="not-detected/' . $provider['proName'] . '/rendering-engines.html" class="btn waves-effect waves-light">
                        Not detected
                    </a>
                </td>
                </tr>
            ';
        } else {
            $html .= '
                <tr>
                <td>
                    Engine name
                </td>
                <td colspan="3" class="center-align red lighten-1">
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
                    ' . round($row['osFound'] / $totalUserAgentsOnePercent, 2) . '%
                    <div class="progress">
                        <div class="determinate" style="width: ' . round($row['osFound'] / $totalUserAgentsOnePercent, 0) . '"></div>
                    </div>
                </td>
                <td>
                    ' . $row['osFound'] . '
                </td>
                <td>
                    <a href="detected/' . $provider['proName'] . '/operating-systems.html" class="btn waves-effect waves-light">
                        Detected
                    </a>
                    <a href="not-detected/' . $provider['proName'] . '/operating-systems.html" class="btn waves-effect waves-light">
                        Not detected
                    </a>
                </td>
                </tr>
            ';
        } else {
            $html .= '
                <tr>
                <td>
                    Operating systems
                </td>
                <td colspan="3" class="center-align red lighten-1">
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
                    ' . round($row['deviceBrandFound'] / $totalUserAgentsOnePercent, 2) . '%
                    <div class="progress">
                        <div class="determinate" style="width: ' . round($row['deviceBrandFound'] / $totalUserAgentsOnePercent, 0) . '"></div>
                    </div>
                </td>
                <td>
                    ' . $row['deviceBrandFound'] . '
                </td>
                <td>
                    <a href="detected/' . $provider['proName'] . '/device-brands.html" class="btn waves-effect waves-light">
                        Detected
                    </a>
                    <a href="not-detected/' . $provider['proName'] . '/device-brands.html" class="btn waves-effect waves-light">
                        Not detected
                    </a>
                </td>
                </tr>
            ';
        } else {
            $html .= '
                <tr>
                <td>
                    Device brands
                </td>
                <td colspan="3" class="center-align red lighten-1">
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
                    ' . round($row['deviceModelFound'] / $totalUserAgentsOnePercent, 2) . '%
                    <div class="progress">
                        <div class="determinate" style="width: ' . round($row['deviceModelFound'] / $totalUserAgentsOnePercent, 0) . '"></div>
                    </div>
                </td>
                <td>
                    ' . $row['deviceModelFound'] . '
                </td>
                <td>
                    <a href="detected/' . $provider['proName'] . '/device-models.html" class="btn waves-effect waves-light">
                        Detected
                    </a>
                    <a href="not-detected/' . $provider['proName'] . '/device-models.html" class="btn waves-effect waves-light">
                        Not detected
                    </a>
                </td>
                </tr>
            ';
        } else {
            $html .= '
                <tr>
                <td>
                    Device models
                </td>
                <td colspan="3" class="center-align red lighten-1">
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
                    ' . round($row['deviceTypeFound'] / $totalUserAgentsOnePercent, 2) . '%
                    <div class="progress">
                        <div class="determinate" style="width: ' . round($row['deviceTypeFound'] / $totalUserAgentsOnePercent, 0) . '"></div>
                    </div>
                </td>
                <td>
                    ' . $row['deviceTypeFound'] . '
                </td>
                <td>
                    <a href="detected/' . $provider['proName'] . '/device-types.html" class="btn waves-effect waves-light">
                        Detected
                    </a>
                    <a href="not-detected/' . $provider['proName'] . '/device-types.html" class="btn waves-effect waves-light">
                        Not detected
                    </a>
                </td>
                </tr>
            ';
        } else {
            $html .= '
                <tr>
                <td>
                    Device types
                </td>
                <td colspan="3" class="center-align red lighten-1">
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
                    ' . round($row['asMobileDetected'] / $totalUserAgentsOnePercent, 2) . '%
                    <div class="progress">
                        <div class="determinate" style="width: ' . round($row['asMobileDetected'] / $totalUserAgentsOnePercent, 0) . '"></div>
                    </div>
                </td>
                <td>
                    ' . $row['asMobileDetected'] . '
                </td>
                <td>
                    <a href="not-detected/' . $provider['proName'] . '/device-is-mobile.html" class="btn waves-effect waves-light">
                        Not detected
                    </a>
                </td>
                </tr>
            ';
        } else {
            $html .= '
                <tr>
                <td>
                    Is mobile
                </td>
                <td colspan="3" class="center-align red lighten-1">
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
                    
                </td>
                <td>
                    ' . round($row['asBotDetected'] / $totalUserAgentsOnePercent, 2) . '%
                    <div class="progress">
                        <div class="determinate" style="width: ' . round($row['asBotDetected'] / $totalUserAgentsOnePercent, 0) . '"></div>
                    </div>
                </td>
                <td>
                    <a href="detected/' . $provider['proName'] . '/bot-is-bot.html" class="btn waves-effect waves-light">
                        Detected
                    </a>
                    <a href="not-detected/' . $provider['proName'] . '/bot-is-bot.html" class="btn waves-effect waves-light">
                        Not detected
                    </a>
                </td>
                </tr>
            ';
        } else {
            $html .= '
                <tr>
                <td>
                    Is bot
                </td>
                <td colspan="3" class="center-align red lighten-1">
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
                </td>
                <td>
                    ' . round($row['botNameFound'] / $totalUserAgentsOnePercent, 2) . '%
                    <div class="progress">
                        <div class="determinate" style="width: ' . round($row['botNameFound'] / $totalUserAgentsOnePercent, 0) . '"></div>
                    </div>
                </td>
                <td>
                    <a href="detected/' . $provider['proName'] . '/bot-names.html" class="btn waves-effect waves-light">
                        Detected
                    </a>
                    <a href="not-detected/' . $provider['proName'] . '/bot-names.html" class="btn waves-effect waves-light">
                        Not detected
                    </a>
                </td>
                </tr>
            ';
        } else {
            $html .= '
                <tr>
                <td>
                    Bot names
                </td>
                <td colspan="3" class="center-align red lighten-1">
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
                </td>
                <td>
                    ' . round($row['botTypeFound'] / $totalUserAgentsOnePercent, 2) . '%
                    <div class="progress">
                        <div class="determinate" style="width: ' . round($row['botTypeFound'] / $totalUserAgentsOnePercent, 0) . '"></div>
                    </div>
                </td>
                <td>
                    <a href="detected/' . $provider['proName'] . '/bot-types.html" class="btn waves-effect waves-light">
                        Detected
                    </a>
                    <a href="not-detected/' . $provider['proName'] . '/bot-types.html" class="btn waves-effect waves-light">
                        Not detected
                    </a>
                </td>
                </tr>
            ';
        } else {
            $html .= '
                <tr>
                <td>
                    Bot types
                </td>
                <td colspan="3" class="center-align red lighten-1">
                    <strong>Not available with this provider</strong>
                </td>
                </tr>
            ';
        }
        
        $html .= '</tbdoy>';
        
        $html .= '</table>';
        
        return $html;
    }

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
}
