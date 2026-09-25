<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuoteCardController extends Controller
{
    public function __invoke(Request $request, int $post): Response
    {
        $story = Post::query()->published()->with('author')->findOrFail($post);
        $user = $request->user();
        abort_if($user?->suspended_at, 403, 'This account is suspended.');

        $data = $request->validate(['quote' => ['required', 'string', 'min:12', 'max:240']]);
        $quote = $this->normalize($data['quote']);
        $body = $this->normalize(preg_replace('~</(?:p|h[1-6]|li|blockquote|div)>|<br\s*/?>~iu', ' ', $story->body_html ?? ''));
        $excerpt = $this->normalize($story->excerpt ?? '');
        if (mb_strlen($quote) < 12 || (! str_contains($body, $quote) && ! str_contains($excerpt, $quote))) {
            throw ValidationException::withMessages(['quote' => 'Select 12–240 characters directly from this story to create a quote card.']);
        }

        $fontCss = $this->fontCss();

        return response($this->render($quote, $story, $fontCss), 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.Str::slug($story->slug).'-quote.svg"',
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "default-src 'none'; style-src 'sha256-".base64_encode(hash('sha256', $fontCss, true))."'; font-src data:; sandbox",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function normalize(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    }

    private function render(string $quote, Post $post, string $fontCss): string
    {
        $escape = fn (string $text): string => htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
        foreach ([58, 52, 46, 40, 34, 30] as $fontSize) {
            $lines = $this->wrap('“'.$quote.'”', 1035 / $fontSize);
            $lineHeight = $fontSize * 1.24;
            if (count($lines) * $lineHeight <= 310) {
                break;
            }
        }
        $baseline = 192 + (310 - count($lines) * $lineHeight) / 2 + $fontSize;
        $text = '';
        foreach ($lines as $index => $line) {
            $text .= '<tspan x="78" y="'.round($baseline + $index * $lineHeight, 1).'">'.$escape($line).'</tspan>';
        }
        $author = $escape($this->fitLine($post->author->name, 1035 / 25));
        $title = $escape($this->fitLine($post->title, 1035 / 19));
        $accessibleTitle = $escape('A quote from '.$post->title.' by '.$post->author->name);
        // This bundled path-only SVG remains self-contained inside downloaded cards.
        $logo = preg_replace('~<\?xml[^>]*\?>~', '', file_get_contents(public_path('images/folkscript-web-primary.svg')));
        $logo = preg_replace('~<svg\b[^>]*>~', '<svg xmlns="http://www.w3.org/2000/svg" x="78" y="68" width="234" height="48" viewBox="0 0 312 64" aria-hidden="true">', $logo, 1);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630" role="img" aria-labelledby="title description">'
            .'<title id="title">'.$accessibleTitle.'</title><desc id="description">'.$escape($quote).'</desc>'
            .'<metadata>'.$escape(file_get_contents(public_path('fonts/lexend-LICENSE.txt'))).'</metadata>'
            .'<style><![CDATA['.$fontCss.']]></style>'
            .'<rect width="1200" height="630" fill="#FFFFFF"/>'
            .$logo
            .'<g fill="#1E2A47" font-family="Lexend Variable, system-ui, sans-serif">'
            .'<text x="1122" y="102" font-size="16" text-anchor="end" fill="#606B81">A thought worth keeping.</text>'
            .'<path d="M78 148H1122" stroke="#1E2A47" stroke-opacity=".2"/>'
            .'<text font-size="'.$fontSize.'">'.$text.'</text>'
            .'<text x="78" y="552" font-size="25">'.$author.'</text>'
            .'<text x="78" y="590" font-size="19" fill="#606B81">'.$title.'</text>'
            .'</g></svg>';
    }

    /** Wrap conservatively for Lexend's open spacing; split long words safely. */
    private function wrap(string $text, float $maximum): array
    {
        $lines = [];
        $line = '';
        $width = 0.0;
        foreach (preg_split('/\s+/u', $text) ?: [] as $word) {
            $pieces = mb_str_split($word);
            $wordWidth = array_sum(array_map($this->glyphWidth(...), $pieces));
            if ($line !== '' && $width + 0.35 + $wordWidth > $maximum) {
                $lines[] = $line;
                $line = '';
                $width = 0;
            }
            if ($line !== '') {
                $line .= ' ';
                $width += 0.35;
            }
            foreach ($pieces as $character) {
                $glyph = $this->glyphWidth($character);
                if ($line !== '' && $width + $glyph > $maximum) {
                    $lines[] = $line;
                    $line = '';
                    $width = 0;
                }
                $line .= $character;
                $width += $glyph;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    private function glyphWidth(string $character): float
    {
        if (str_contains('ilj.,:;!|\'’', $character)) {
            return 0.36;
        }
        if (str_contains('MWmw@%&', $character) || mb_strlen($character, '8bit') > 2) {
            return 1.05;
        }

        return preg_match('/\p{Lu}/u', $character) ? 0.8 : 0.66;
    }

    private function fitLine(string $text, float $maximum): string
    {
        $width = 0.0;
        $fitted = '';
        foreach (mb_str_split($text) as $character) {
            $width += $this->glyphWidth($character);
            if ($width > $maximum - 1.05) {
                return rtrim($fitted).'…';
            }
            $fitted .= $character;
        }

        return $fitted;
    }

    /** Keep downloaded cards independent of remote font servers. */
    private function fontCss(): string
    {
        return preg_replace_callback(
            '~url\([\'"]?/fonts/(lexend-[a-z-]+\.woff2)[\'"]?\)~',
            fn (array $match): string => 'url(data:font/woff2;base64,'.base64_encode(file_get_contents(public_path('fonts/'.$match[1]))).')',
            file_get_contents(resource_path('css/fonts.css')),
        );
    }
}
