<?php

namespace App\Observers;

use App\Models\Faq;
use App\Services\SiteContentService;

class FaqObserver
{
    public function __construct(private readonly SiteContentService $cache) {}

    public function saved(Faq $faq): void
    {
        $this->cache->invalidarFaqs();
    }

    public function deleted(Faq $faq): void
    {
        $this->cache->invalidarFaqs();
    }
}
