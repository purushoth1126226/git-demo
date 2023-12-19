<div>
    <div class="card shadow-sm">
        <div class="card-header text-white theme_bg_color p-1">
            <div class="d-flex flex-row bd-highlight">
                <div class="flex-grow-1 bd-highlight mt-1"><span class="h5">{{ __('CURRENT STOCK REPORTS') }}</span>
                </div>
                <div class="bd-highlight d-flex gap-1">
                    <a class="btn btn-sm btn-secondary shadow float-end mx-1"href="{{ route('adminreports') }}"
                        role="button">{{ __('Back') }}</a>
                </div>
            </div>

        </div>
        <div class="card-body p-0">
            <div class="row g-3 align-items-center p-2">
                <div class="col-auto">
                    <label for="startdateid" class="col-form-label fw-bold fs-6"> {{ __('From Date') }} :
                    </label>

                </div>
                <div class="col-auto">
                    <input type="date" wire:model="from_date" class="form-control form-control-sm" id="startdateid">
                </div>


                <div class="col-auto">
                    <label for="enddateid" class="col-form-label fw-bold fs-6">
                        {{ __('To Date ') }} : </label>
                </div>
                <div class="col-auto">
                    <input type="date" wire:model="to_date" class="form-control form-control-sm" id="enddateid">
                </div>

                <div class="col-auto">
                    <input type="text" wire:model="searchTerm" id="searchitem" class="form-control form-control-sm"
                        placeholder={{ __('Search') }} aria-describedby="passwordHelpInline">
                </div>

                <div class="col-auto text-start mt-3">
                    <button wire:click="export" class="btn btn-sm btn-success fw-bold">  {{ __('Excel') }}
                        <i class="bi bi-arrow-down"></i></button>
                    <button wire:click="pdf" class="btn btn-sm btn-success fw-bold"> {{ __('PDF') }}
                        <i class="bi bi-arrow-down"></i></button>
                    <button wire:click="clear" class="btn btn-sm btn-secondary"> {{ __('Clear') }}</button>
                </div>


            </div>
            <div class="table-responsive">
                <table id="purchasereportreport_id" class="table text-center table-hover m-0 p-0">
                    <thead>
                        <tr
                            class="text-white table-{{ App::make('themesetting') ? strtolower(App::make('themesetting')->theme_name) : 'teal' }}">
                            <th>{{ __('S.NO') }}</th>
                            <th>{{ __('PRODUCT ID') }}</th>
                            <th>{{ __('PRODUCT NAME') }}</th>
                            <th>{{ __('SKU') }}</th>
                            <th>{{ __('CURRENT STOCK') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->currentstock as $eachcurrentstock)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $eachcurrentstock->uniqid }}</td>
                                <td>{{ $eachcurrentstock->name }}</td>
                                <td class="text-center">{{ $eachcurrentstock->sku }}</td>
                                <td class="text-center">{{ $eachcurrentstock->stock }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-2">
                {!! $this->currentstock->links() !!}
            </div>
        </div>
    </div>
</div>
