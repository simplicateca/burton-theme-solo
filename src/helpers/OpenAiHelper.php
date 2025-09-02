<?php

namespace simplicateca\burtonsolo\helpers;

use OpenAI;
use nystudio107\seomatic\helpers\Text as SeoMaticTextHelper;

use craft\elements\Entry;
use yii\base\Event;

use craft\base\Element;
use craft\events\ModelEvent;
use craft\helpers\ElementHelper;

class OpenAiHelper {

    private const MODEL = 'gpt-4';
    private const PROMPTS = [
        'entrySummary' => "You are a helpful and detail-oriented SEO copywriter. Complete the following steps one-by-one for each batch of PAGE CONTENT provided by the user.

1. Read and understand the PAGE CONTENT provided by the user.
   - If the content includes placeholder text such as 'lorem ipsum,' ignore it when creating the summary.
   - If placeholder text reduces the amount of usable content, still generate all required output, but keep the summary more general, accurate, and concise rather than speculative.

2. Create a short teaser-style Summary Card and Overview in plain HTML. The output must include:
   - A concise page name inside an <h3> tag.
     - Keep it short (2–4 words).
     - Incorporate a primary keyword inferred from PAGE CONTENT where natural.
     - Do not use curiosity-driven phrasing here; keep it descriptive and professional.
   - A teaser paragraph inside a <p> tag.
     - Max 150 characters (including spaces).
     - Begin with or prominently feature a bolded key-phrase using <strong> tags.
     - Written in a tone that doubles as both meta description and teaser text.
     - Prioritize clarity and SEO value.
   - A page overview/abstract that provides a clear summary of the PAGE CONTENT.
     - About 100 words (~600 characters).
     - Maximum total length of all output (h3 + teaser + overview) is ~135 words / ~900 characters.
     - May use <p>, <ul>, <li>, <strong>, <em>, and <mark> tags.
     - Provide 2–3 short paragraphs and/or a brief bullet list.
     - Tone should support readers in the 'research phase' of their journey, leaning descriptive rather than promotional.

3. Style and content rules:
   - Omit the brand or site name in the teaser (optional in the abstract).
   - Avoid explicit calls-to-action.
   - Do not introduce details that are not present in PAGE CONTENT.
   - Use Canadian English spelling.
   - Avoid dates or numbers unless they appear in the PAGE CONTENT.
   - Write at a university/college reading level.

Output only the required HTML elements (<h3>, <p>, <ul>, <li>, <strong>, <em>, <mark>) with no additional labels, counts, or commentary.",

        'entryOutline' =>
            "You are a helpful and detail-oriented SEO copywriter. Complete the following steps one-by-one for each batch of PAGE CONTENT provided by the user.

1. Read and understand the PAGE CONTENT provided by the user.
   - If the content includes placeholder text such as 'lorem ipsum,' ignore it when creating the outline.
   - If placeholder text reduces the amount of usable content, still generate all required output, but keep the outline more general, accurate, and curiosity-driven rather than speculative.

2. Create a 'Things You Will Learn/Understand/Takeaway' style outline that encourages readers to continue engaging with the content.
   - The outline must include exactly 3 bullet points inside a single <ul> list.
   - Each bullet point should highlight one of the 3 most compelling reasons to keep reading.
   - Emphasize curiosity, appeal, and engagement over factual detail.
   - Keep each bullet concise, with no more than 15 words.

3. Style and content rules:
   - Avoid explicit calls-to-action.
   - Do not introduce details not present in PAGE CONTENT.
   - Use Canadian English spelling.
   - Write at a university/college reading level.

Output only the final <ul><li>...</li></ul> list with no additional text, labels, or commentary.",
    ];


    public static function listeners() {
        Event::on(
            Entry::class,
            Element::EVENT_BEFORE_SAVE,
            function( ModelEvent $e ) {
                $entry = $e->sender;
                if (ElementHelper::isDraftOrRevision($entry)) { return; }

                // If the text is too short, don't run the prompts
                $rawText = self::rawText( $entry );
                if( strlen( $rawText ) < 100 ) { return ; }

                self::autoCompleteFields( $entry, $rawText );
            }
        );
    }


    public static function autoCompleteFields( $entry, $rawText = "" ): void
    {
        // Intro Outline
        if (($entry->details->fieldValues['autoOutline'] ?? false) === true) {
            $outline = self::runPrompt('entryOutline', $rawText);
            $entry->setFieldValue('details', [
                'fields' => array_merge($entry->details->getSerializedFieldValues(), [
                    'outline' => $outline,
                    'autoOutline' => false,
                ]),
            ]);
        }

        // Card Summary
        if (($entry->card->fieldValues['autoSummary'] ?? false) === true) {
            $summary = self::runPrompt('entrySummary', $rawText);
            $entry->setFieldValue('card', [
                'fields' => array_merge($entry->card->getSerializedFieldValues(), [
                    'summary' => $summary,
                    'autoSummary' => false,
                ]),
            ]);
        }
    }


    public static function runPrompt( $prompt = "", $content = "" ): string
    {
        if( $client = self::client() ) {
            $response = $client->chat()->create([
                'model'    => self::MODEL,
                'messages' => [
                    ['role' => 'system', 'content' => self::PROMPTS[$prompt]],
                    ['role' => 'user',   'content' => "PAGE CONTENT:\n" . $content ],
                ],
            ]);

            $gpt = trim( $response->choices[0]->message->content ?? null );
            $gpt = trim( $gpt, '"' );

            return $gpt;
        }

        return "";
    }


    public static function client()
    {
        return getenv('OPENAI_SECRET')
            ? OpenAI::client( getenv('OPENAI_SECRET') )
            : false;
    }


    public static function rawText( $entry )
    {
        $values = $entry->getFieldValues();

        if( $values['details'] ?? null ) {
            $values['details'] = $values['details']->getFieldValues();
        }

        if( $values['card'] ?? null ) {
            $values['card'] = $values['card']->getFieldValues();
        }

        $text = join(" ", [
            $values['title'] ?? null,
            $values['details']['short']   ?? null,
            $values['details']['long']    ?? "",
            SeoMaticTextHelper::extractTextFromMatrix( $values['headerBuilder']  ?? null ),
            $values['details']['outline'] ?? "",
            $values['card']['summary']    ?? "",
            $values['summary'] ?? null,
            SeoMaticTextHelper::extractTextFromMatrix( $values['bodyBuilder']    ?? null ),
            $values['text'] ?? null,
            SeoMaticTextHelper::extractTextFromMatrix( $values['sidebarBuilder'] ?? null ),
        ] );

        $text = mb_ereg_replace( "</", " </", $text );
        $text = strip_tags( $text );
        $text = mb_ereg_replace( "\n", " ",  $text );
        $text = mb_ereg_replace( "\s+", " ", $text );

        return mb_trim( $text );
    }
}