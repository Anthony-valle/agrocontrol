<main id="main" class="main">
    <div class="pagetitle">
        <h1>{{ $title }}</h1>
        @isset($subtitle)
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        @endisset
    </div>

    <section class="section">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" style="width:56px; height:56px;">
                        <i class="{{ $icon ?? 'fa-solid fa-chart-column' }} text-primary fs-4"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="card-title pt-0 mb-2">{{ $title }}</h5>
                        <p class="text-muted mb-3">{{ $message }}</p>
                        @isset($actions)
                            <div class="d-flex flex-wrap gap-2">
                                {!! $actions !!}
                            </div>
                        @endisset
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>