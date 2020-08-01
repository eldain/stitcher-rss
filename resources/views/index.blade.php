@extends('layout')

@section('content')
    <div class="columns is-desktop">
        <div class="column is-half is-offset-one-quarter">
            <div class="notification is-danger">
                This site was shut down down on July 31st, 2020. These
                feeds were fan-created and run. See
                <a href="https://reddit.com/r/Earwolf/comments/hmxwf5/">this
                post</a> for more information. You'll need to
                unsubscribe from feeds from this site and use the Stitcher
                app if you want to continue to listen to Stitcher Premium
                content.
            </div>
        </div>
    </div>

    <div class="columns is-desktop">
        <div class="column is-half is-offset-one-quarter content">
            <h2 class="title is-3">Frequently Asked Questions</h2>
            <strong>What was this?</strong>
            <p>
                This was a service that provided RSS feeds for premium
                content available to Stitcher subscribers. These RSS
                feeds could be added to most podcast clients (Pocket Casts,
                Podcast Addict, iTunes, etc.) to play Stitcher content
                like any other podcast.
            </p>
        </div>
    </div>
@endsection
