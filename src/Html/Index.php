<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Html;

final class Index extends AbstractHtml
{
    /** @throws void */
    public function getHtml(): string
    {
        $body = '
<div class="section">
    <h1 class="header center orange-text">UserAgentParserComparison comparison</h1>

    <div class="row center">
        <p>
            See the comparison of different user agent parsers
        </p>

        ' . $this->getButtons() . '

        by Thomas Müller (@mimmi20)
    </div>
</div>
';

        return parent::getHtmlCombined($body);
    }

    /** @throws void */
    private function getButtons(): string
    {
        $html = '';

        for ($i = COMPARISON_VERSION; 0 < $i; --$i) {
            $txt = 'Version ' . $i;

            if ($i === COMPARISON_VERSION) {
                $txt .= ' (latest)';
            }

            $html .= '
                <a class="modal-trigger btn waves-effect waves-light"
                    href="v' . $i . '/index.html">
                    ' . $txt . '
                </a><br /><br />
            ';
        }

        return $html;
    }
}
