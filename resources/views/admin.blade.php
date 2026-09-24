@php
    $pages = [
        'overview' => [__('Overview'), __('A clear view of your publication and what needs your attention.')],
        'reports' => [__('Reports'), __('Review concerns from readers and decide what happens next.')],
        'comments' => [__('Comments'), __('Keep conversations welcoming. Review, hide, or restore responses.')],
        'stories' => [__('Stories'), __('Find and manage writing across the publication.')],
        'people' => [__('People'), __('Find an account, check its status, and manage access.')],
        'topics' => [__('Categories & tags'), __('Help readers discover stories by subject.')],
        'activity' => [__('Activity log'), __('Recent changes to content, accounts, and publication settings. Times shown in UTC.')],
    ];
    $returnQuery = request()->only(['view', 'q', 'story_q', 'user_status', 'report_status', 'comment_status', 'story_status', 'users_page', 'reports_page', 'comments_page', 'posts_page']);
    $returnQuery['view'] = $section;
@endphp
<x-admin-layout :title="$pages[$section][0]" :description="$pages[$section][1]" :section="$section" :counts="$stats">
    <div class="admin-page">
        @include('admin.'.$section)
    </div>
</x-admin-layout>
