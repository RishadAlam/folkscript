@props(['comment'])
@if(auth()->user()?->hasVerifiedEmail())
<div class="comment-tools">
    @can('delete', $comment)
    <form method="POST" action="/comments/{{ $comment->id }}" onsubmit="return confirm(@js($comment->parent_id ? __('Remove this reply from the conversation?') : __('Remove this response and its replies from the conversation?')))">
        @csrf @method('DELETE')
        <button type="submit" aria-label="{{ __('Remove response by :name', ['name' => $comment->user->name]) }}">{{ __('Remove') }}</button>
    </form>
    @endcan
    @if(auth()->id() !== $comment->user_id)
    <details class="comment-report" @if($errors->getBag('commentReport')->any() && (string)old('comment_id') === (string)$comment->id) open @endif>
        <summary>{{ __('Report') }}<span class="sr-only"> {{ __('response by :name', ['name' => $comment->user->name]) }}</span></summary>
        <form method="POST" action="/comments/{{ $comment->id }}/report" class="stack">
            @csrf<input type="hidden" name="comment_id" value="{{ $comment->id }}">
            <div class="field comment-field">
            <label for="comment-report-{{ $comment->id }}">{{ __('Tell our editors what needs attention') }}</label>
            <textarea id="comment-report-{{ $comment->id }}" name="reason" class="form-input" rows="3" required minlength="10" maxlength="2000" aria-describedby="comment-report-{{ $comment->id }}-help comment-report-{{ $comment->id }}-error" aria-invalid="{{ $errors->getBag('commentReport')->has('reason') && (string)old('comment_id') === (string)$comment->id ? 'true' : 'false' }}">{{ (string)old('comment_id') === (string)$comment->id ? old('reason') : '' }}</textarea>
            <small id="comment-report-{{ $comment->id }}-help">{{ __('Explain the concern in at least 10 characters. Reports are sent privately to the editorial team.') }}</small>
            @if((string)old('comment_id') === (string)$comment->id)<x-field-error name="reason" bag="commentReport" :id="'comment-report-'.$comment->id.'-error'" />@else<span id="comment-report-{{ $comment->id }}-error" hidden></span>@endif
            </div>
            <div><button type="submit" class="btn btn-outline btn-small">{{ __('Send report') }}</button></div>
        </form>
    </details>
    @endif
</div>
@endif
