@extends('layouts.app')

@section('title', 'Import inventory')

@section('content')
<div class="page-header"><div><h1>Import inventory</h1><p class="page-header__meta">Upload a CSV file to preview and confirm import</p></div></div>

<div class="card" style="max-width:560px;">
    <div class="card__body">
        <form method="post" action="{{ route('import.preview') }}" enctype="multipart/form-data">@csrf
            <div class="form-group">
                <label class="form-label" for="file">CSV file</label>
                <input type="file" name="file" id="file" class="form-control" accept=".csv,.txt" required>
                <span class="form-hint">Required columns include at least item_code and name. Max 10 MB.</span>
            </div>
            <button type="submit" class="btn btn--primary mt-2">Preview import</button>
        </form>
    </div>
</div>

<div class="card mt-2"><div class="card__header"><h2 class="card__title">CSV template</h2></div>
<div class="card__body"><pre style="font-size:0.85rem;overflow:auto;background:#f8fafc;padding:1rem;border-radius:8px;">item_code,name,description,quantity,unit,unit_cost,condition,status</pre></div></div>
@endsection
