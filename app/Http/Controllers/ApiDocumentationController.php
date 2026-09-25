<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ApiDocumentationController extends Controller
{
    public function index(): View
    {
        $source = file_get_contents(base_path('docs/API.md'));
        $source = preg_replace('/^# [^\n]+\n/', '', $source, 1);
        $html = Str::markdown($source, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
        $sections = [];
        $html = preg_replace_callback('/<h2>(.*?)<\/h2>/s', function (array $match) use (&$sections) {
            $title = html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $id = Str::slug($title);
            $sections[] = ['id' => $id, 'title' => $title];

            return '<h2 id="'.e($id).'">'.$match[1].'</h2>';
        }, $html);
        // Keep wide schemas keyboard-scrollable without widening the reading page.
        $html = preg_replace('/<table>(.*?)<\/table>/s', '<div class="api-table-scroll" role="region" aria-label="API reference table" tabindex="0"><table>$1</table></div>', $html);
        $html = str_replace('<pre>', '<pre tabindex="0" aria-label="Code example">', $html);

        return view('developers.api', compact('html', 'sections'));
    }

    public function markdown(): Response
    {
        return response(file_get_contents(base_path('docs/API.md')), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline; filename="folkscript-api.md"',
        ]);
    }
}
