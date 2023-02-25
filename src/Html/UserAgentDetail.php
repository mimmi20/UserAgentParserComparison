<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Html;

use JsonException;

use function array_key_exists;
use function count;
use function htmlspecialchars;
use function json_decode;
use function print_r;
use function round;

use const JSON_THROW_ON_ERROR;

final class UserAgentDetail extends AbstractHtml
{
    /** @var array<mixed> */
    private array $userAgent = [];

    /** @var array<array<mixed>> */
    private array $results = [];

    /**
     * @param array<mixed> $userAgent
     *
     * @throws void
     */
    public function setUserAgent(array $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    /**
     * @param array<array<mixed>> $results
     *
     * @throws void
     */
    public function setResults(array $results): void
    {
        $this->results = $results;
    }

    /** @throws JsonException */
    public function getHtml(): string
    {
        $addStr = '';

        if (null !== $this->userAgent['uaAdditionalHeaders']) {
            $addHeaders = json_decode($this->userAgent['uaAdditionalHeaders'], true, 512, JSON_THROW_ON_ERROR);

            if (0 < count($addHeaders)) {
                $addStr = '<br /><strong>Additional headers</strong><br />';

                foreach ($addHeaders as $key => $value) {
                    $addStr .= '<strong>' . htmlspecialchars($key) . '</strong> ' . htmlspecialchars($value) . '<br />';
                }
            }
        }

        $body = '
<div class="section">
    <h1 class="header center orange-text">User agent detail</h1>
    <div class="row center">
        <h5 class="header light">
            ' . htmlspecialchars($this->userAgent['uaString']) . '
            ' . $addStr . '
        </h5>
    </div>
</div>

<div class="section">
    ' . $this->getProvidersTable() . '
</div>
';

        $script = '
$(document).ready(function(){
    // the "href" attribute of .modal-trigger must specify the modal ID that wants to be triggered
    $(\'.modal-trigger\').leanModal();
});
        ';

        return parent::getHtmlCombined($body, $script);
    }

    /** @throws void */
    private function getProvidersTable(): string
    {
        $html = '<table class="striped">';

        $html .= '<tr>';
        $html .= '<th></th>';
        $html .= '<th colspan="3">General</th>';
        $html .= '<th colspan="5">Device</th>';
        $html .= '<th colspan="3">Bot</th>';
        $html .= '<th colspan="2"></th>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Provider</th>';
        $html .= '<th>Browser</th>';
        $html .= '<th>Engine</th>';
        $html .= '<th>OS</th>';

        $html .= '<th>Brand</th>';
        $html .= '<th>Model</th>';
        $html .= '<th>Type</th>';
        $html .= '<th>Is mobile</th>';
        $html .= '<th>Is touch</th>';

        $html .= '<th>Is bot</th>';
        $html .= '<th>Name</th>';
        $html .= '<th>Type</th>';

        $html .= '<th>Parse time</th>';
        $html .= '<th>Actions</th>';

        $html .= '</tr>';

        /*
         * Test suite
         */
        $html .= '<tr><th colspan="14" class="green lighten-3">';
        $html .= 'Test suite';
        $html .= '</th></tr>';

        foreach ($this->results as $result) {
            if (!array_key_exists('proType', $result) || 'testSuite' !== $result['proType']) {
                continue;
            }

            $html .= $this->getRow($result);
        }

        /*
         * Providers
         */
        $html .= '<tr><th colspan="14" class="green lighten-3">';
        $html .= 'Providers';
        $html .= '</th></tr>';

        foreach ($this->results as $result) {
            if (!array_key_exists('proType', $result) || 'real' !== $result['proType']) {
                continue;
            }

            $html .= $this->getRow($result);
        }

        $html .= '</table>';

        return $html;
    }

    /** @throws void */
    private function getRow(array $result): string
    {
        $html = '<tr>';

        $html .= '<td>' . $result['proName'] . '<br />';
        $html .= '<small>' . $result['proVersion'] . '</small><br /><small>' . $result['proLastReleaseDate'] . '</small>';

        if ($result['resFilename']) {
            $html .= '<br /><small>' . $result['resFilename'] . '</small>';
        }

        $html .= '</td>';

        if (!$result['resResultFound']) {
            $html .= '
                    <td colspan="12" class="center-align red lighten-1">
                        <strong>No result found</strong>
                    </td>
                ';

            $html .= '</tr>';

            return $html;
        }

        /*
         * General
         */
        if ($result['proCanDetectBrowserName']) {
            $html .= '<td>' . $result['resBrowserName'] . ' ' . $result['resBrowserVersion'] . '</td>';
        } else {
            $html .= '<td><i class="material-icons">close</i></td>';
        }

        if ($result['proCanDetectEngineName']) {
            $html .= '<td>' . $result['resEngineName'] . ' ' . $result['resEngineVersion'] . '</td>';
        } else {
            $html .= '<td><i class="material-icons">close</i></td>';
        }

        if ($result['proCanDetectOsName']) {
            $html .= '<td>' . $result['resOsName'] . ' ' . $result['resOsVersion'] . '</td>';
        } else {
            $html .= '<td><i class="material-icons">close</i></td>';
        }

        /*
         * Device
         */
        if ($result['proCanDetectDeviceBrand']) {
            $html .= '<td style="border-left: 1px solid #555">' . $result['resDeviceBrand'] . '</td>';
        } else {
            $html .= '<td style="border-left: 1px solid #555"><i class="material-icons">close</i></td>';
        }

        if ($result['proCanDetectDeviceModel']) {
            $html .= '<td>' . $result['resDeviceModel'] . '</td>';
        } else {
            $html .= '<td><i class="material-icons">close</i></td>';
        }

        if ($result['proCanDetectDeviceType']) {
            $html .= '<td>' . $result['resDeviceType'] . '</td>';
        } else {
            $html .= '<td><i class="material-icons">close</i></td>';
        }

        if ($result['proCanDetectDeviceIsMobile']) {
            if ($result['resDeviceIsMobile']) {
                $html .= '<td>yes</td>';
            } else {
                $html .= '<td></td>';
            }
        } else {
            $html .= '<td><i class="material-icons">close</i></td>';
        }

        if ($result['proCanDetectDeviceIsTouch']) {
            if ($result['resDeviceIsTouch']) {
                $html .= '<td>yes</td>';
            } else {
                $html .= '<td></td>';
            }
        } else {
            $html .= '<td><i class="material-icons">close</i></td>';
        }

        /*
         * Bot
         */
        if ($result['proCanDetectBotIsBot']) {
            if ($result['resBotIsBot']) {
                $html .= '<td style="border-left: 1px solid #555">yes</td>';
            } else {
                $html .= '<td style="border-left: 1px solid #555"></td>';
            }
        } else {
            $html .= '<td style="border-left: 1px solid #555"><i class="material-icons">close</i></td>';
        }

        if ($result['proCanDetectBotName']) {
            $html .= '<td>' . $result['resBotName'] . '</td>';
        } else {
            $html .= '<td><i class="material-icons">close</i></td>';
        }

        if ($result['proCanDetectBotType']) {
            $html .= '<td>' . $result['resBotType'] . '</td>';
        } else {
            $html .= '<td><i class="material-icons">close</i></td>';
        }

        if (null === $result['resParseTime']) {
            $html .= '<td></td>';
        } else {
            $html .= '<td>' . round($result['resParseTime'], 5) . '</td>';
        }

        $html .= '<td>

<!-- Modal Trigger -->
<a class="modal-trigger btn waves-effect waves-light" href="#modal-' . $result['proId'] . '">Detail</a>

<!-- Modal Structure -->
<div id="modal-' . $result['proId'] . '" class="modal modal-fixed-footer">
    <div class="modal-content">
        <h4>' . $result['proName'] . ' result detail</h4>
        <p><pre><code class="php">' . print_r($result['resRawResult'], true) . '</code></pre></p>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-action modal-close waves-effect waves-green btn-flat ">close</a>
    </div>
</div>

                </td>';

        $html .= '</tr>';

        return $html;
    }
}
