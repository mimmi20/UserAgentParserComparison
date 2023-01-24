<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Html;

use PDO;

use function date;
use function htmlspecialchars;
use function mb_substr;
use function round;

abstract class AbstractHtml
{
    private int | null $userAgentCount = null;

    public function __construct(protected PDO $pdo, protected string | null $title = null)
    {
    }

    abstract public function getHtml(): string;

    protected function getUserAgentCount(): int
    {
        if (null === $this->userAgentCount) {
            $statementCountAllResults = $this->pdo->prepare('SELECT COUNT(*) AS `count` FROM `userAgent`');
            $statementCountAllResults->execute();

            $this->userAgentCount = $statementCountAllResults->fetch(PDO::FETCH_COLUMN);
        }

        return $this->userAgentCount;
    }

    protected function getPercentageMarkup(int $resultFound): string
    {
        $count      = $this->getUserAgentCount();
        $onePercent = $count / 100;

        return '
            <span>' . round($resultFound / $onePercent, 2) . '%</span>
            <!--
            <div class="progress">
                <div class="determinate" style="width: ' . round($resultFound / $onePercent, 0) . '"></div>
            </div>
            -->
            <progress value="' . $resultFound . '" max="' . $count . '" title="' . $resultFound . '/' . $count . ' [' . round($resultFound / $onePercent, 2) . '%]"></progress>
        ';
    }

    protected function getUserAgentUrl(string $uaId): string
    {
        $url  = '../../user-agent-detail/' . mb_substr($uaId, 0, 2) . '/' . mb_substr($uaId, 2, 2);
        $url .= '/' . $uaId . '.html';

        return $url;
    }

    protected function getHtmlCombined(string $body, string $script = ''): string
    {
        return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />

    <title>' . htmlspecialchars($this->title) . '</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <style>
        progress {width: 5em}
    </style>
</head>

<body>
<div class="container">
    ' . $body . '

    <div class="section">
        <h1 class="header center orange-text">About this comparison</h1>

        <div class="row center">
            <h5 class="header light">
                The primary goal of this project is simple<br />

                I wanted to know which user agent parser is the most accurate in each part - device detection, bot detection and so on...<br />
                <br />
                The secondary goal is to provide a source for all user agent parsers to improve their detection based on this results.<br />
                <br />
                You can also improve this further, by suggesting ideas at <a href="https://github.com/mimmi20/UserAgentParserComparison">mimmi20/UserAgentParserComparison</a><br />
                <br />
                The comparison is based on the abstraction by <a href="https://github.com/mimmi20/UserAgentParserComparison">mimmi20/UserAgentParserComparison</a>
            </h5>
        </div>

    </div>

    <div class="card">
        <div class="card-content">
            Comparison created <i>' . date('Y-m-d H:i:s') . '</i> | by
            <a href="https://github.com/mimmi20">mimmi20</a>
        </div>
    </div>

</div>

    <script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script src="http://cdnjs.cloudflare.com/ajax/libs/list.js/1.2.0/list.min.js"></script>

    <script>
    ' . $script . '
    </script>

</body>
</html>';
    }
}
