<?php

namespace App\Observers;

use App\Models\Consultant;

class ConsultantObserver
{
    /**
     * Handle the Consultant "created" event.
     */
    public function created(Consultant $consultant): void
    {
        //
    }

    /**
     * Handle the Consultant "updated" event.
     */
    public function updated(Consultant $consultant): void
    {
        //
    }

    /**
     * Handle the Consultant "deleted" event.
     */
    public function deleted(Consultant $consultant): void
    {
        //
    }

    /**
     * Handle the Consultant "restored" event.
     */
    public function restored(Consultant $consultant): void
    {
        //
    }

    /**
     * Handle the Consultant "force deleted" event.
     */
    public function forceDeleted(Consultant $consultant): void
    {
        //
    }
}
