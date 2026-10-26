<footer class="aq-footer">
    <div class="aq-container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <p class="mb-0">
            <strong class="aq-ink">AQDesk</strong> · Community, learning and knowledge platform
            · &copy; {{ now()->year }}
        </p>
        <nav aria-label="Footer">
            <ul class="aq-footer-links">
                <li><a href="{{ route('questions.index') }}">Questions</a></li>
                <li><a href="{{ route('jobs.index') }}">Opportunities</a></li>
                <li><a href="{{ route('guidelines') }}">Guidelines</a></li>
                <li><a href="{{ route('terms') }}">Terms</a></li>
                <li><a href="{{ route('privacy') }}">Privacy</a></li>
                @guest
                    <li><a href="{{ route('register') }}">Join AQDesk</a></li>
                @endguest
            </ul>
        </nav>
    </div>
</footer>
