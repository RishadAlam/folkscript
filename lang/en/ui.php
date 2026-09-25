<?php

return [
    'writer_count' => '{0} 0 writers|{1} :count writer|[2,*] :count writers',
    'story_count' => '{0} 0 stories|{1} :count story|[2,*] :count stories',
    'follower_count' => '{0} 0 followers|{1} :count follower|[2,*] :count followers',
    'open_report_count' => '{0} 0 open reports|{1} :count open report|[2,*] :count open reports',
    'reading_minutes' => '{1} :count min read|[2,*] :count min read',
    'search_story_count' => '{0} 0 stories for “:query”|{1} :count story for “:query”|[2,*] :count stories for “:query”',
    'status' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'scheduled' => 'Scheduled',
        'archived' => 'Archived',
    ],
    'roles' => [
        'reader' => 'Reader',
        'author' => 'Author',
        'editor' => 'Editor',
        'admin' => 'Admin',
        'super-admin' => 'Super admin',
    ],
];
