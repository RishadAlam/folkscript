<x-layout :title="$post ? __('Editing: :title', ['title' => $post->title]) : __('Write your next story')">
@livewire('post-editor', ['post' => $post])
</x-layout>
