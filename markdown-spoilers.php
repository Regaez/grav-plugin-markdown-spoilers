<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

/**
 * Class MarkdownSpoilersPlugin
 * @package Grav\Plugin
 */
class MarkdownSpoilersPlugin extends Plugin
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onMarkdownInitialized' => ['onMarkdownInitialized', 0],
            'onTwigSiteVariables'   => ['onTwigSiteVariables', 0]
        ];
    }

    public function onMarkdownInitialized(Event $event)
    {
        $markdown = $event['markdown'];

        $markdown->addBlockType('>', 'Spoilers', true, false, 0);

        $markdown->blockSpoilers = function($Line) {
            // Matches spoiler syntax:
            // >? [optional label] some spoiler message
            if (preg_match('/^(>\?)(?:\s?\[([^\]]*)\]\s?)?(.*)/', $Line['text'], $matches))
            {
                $label = ltrim($matches[2]);
                $text = ltrim($matches[3]);

                $Element = [
                    'name' => 'p',
                    'handler' => 'line',
                    'attributes' => [
                        'class' => 'md-spoiler__line'
                    ],
                    'text' => $text,
                ];

                $Attributes = [
                    'class' => 'md-spoiler'
                ];

                // ignore the label if empty
                if (0 < strlen($label)) {
                    $Attributes['data-label'] = $label;
                }

                $Block = [
                    'element' => [
                        'name' => 'div',
                        'handler' => 'elements',
                        'attributes' => $Attributes,
                        'text' => [ $Element ],
                    ]
                ];

                return $Block;
            }
        };

        $markdown->blockSpoilersContinue = function($Line, array $Block) {
            if (isset($Block['interrupted']))
            {
                return;
            }

            // We don't support labels for continued lines
            // Matches spoiler syntax:
            // >? this is a spoiler
            if ($Line['text'][0] === '>' and preg_match('/^(>\?)(.*)/', $Line['text'], $matches))
            {
                $text = ltrim($matches[2]);

                $Element = [
                    'name' => 'p',
                    'handler' => 'line',
                    'attributes' => [
                        'class' => 'md-spoiler__line'
                    ],
                    'text' => $text,
                ];

                // we ignore empty content lines
                if (0 < strlen($Element['text'])) {
                    $Block['element']['text'] []= $Element;
                }

                return $Block;
            }
        };

        $markdown->addInlineType('?', 'Spoilers');

        $markdown->inlineSpoilers = function($Excerpt) {
            // Matches spoiler syntax:
            // ??[optional label] some spoiler message??
            if (preg_match('/^(\?{2})(?:\[([^\]]*)\]\s*)?(\S[^\?]*\S)(\?{2})/', $Excerpt['text'], $matches))
            {
                $extent = strlen($matches[0]);
                $label = ltrim($matches[2]);
                $text = ltrim($matches[3]);

                $Attributes = [
                    'class' => 'md-spoiler md-spoiler--inline'
                ];

                // ignore the label if empty
                if (0 < strlen($label)) {
                    $Attributes['data-label'] = $label;
                }

                $Span = [
                    'name' => 'span',
                    'handler' => 'line',
                    'attributes' => [
                        'class' => 'md-spoiler__line'
                    ],
                    'text' => $text,
                ];

                $SpoilerTag = [
                    'extent' => $extent,
                    'element' => [
                        'name' => 'span',
                        'handler' => 'element',
                        'attributes' => $Attributes,
                        'text' => $Span,
                    ]
                ];

                return $SpoilerTag;
            }
        };
    }

    public function onTwigSiteVariables()
    {
        if ($this->config->get('plugins.markdown-spoilers.include_css')) {
            $this->grav['assets']
                ->add('plugin://markdown-spoilers/assets/spoilers.css');
        }
    }
}
