@extends('layouts.admin')

@section('title', 'Posters & Announcements')

@push('styles')
<style>
    .status-note{
        background:var(--green-100);color:var(--green-700);border:1px solid var(--green-500);
        border-radius:12px;padding:11px 16px;font-size:13px;font-weight:700;margin-bottom:18px;
    }
    .poster-toolbar{display:flex;justify-content:flex-end;margin-bottom:18px;}

    .poster-grid{
        display:grid;grid-template-columns:repeat(auto-fill, minmax(260px, 1fr));
        gap:18px;
    }
    .poster-card{
        border:1px solid var(--line);border-radius:var(--radius-lg);overflow:hidden;
        background:#fff;box-shadow:var(--shadow);display:flex;flex-direction:column;
    }
    .poster-card__img{
        height:150px;background-size:cover;background-position:center;
        background-color:var(--green-100);position:relative;
    }
    .poster-card__status{
        position:absolute;top:10px;left:10px;font-size:10.5px;font-weight:800;
        text-transform:uppercase;letter-spacing:.04em;padding:4px 9px;border-radius:999px;
    }
    .poster-card__status.status-open{background:rgba(31,174,116,.9);color:#fff;}
    .poster-card__status.status-full{background:rgba(239,141,61,.92);color:#fff;}
    .poster-card__status.status-ongoing{background:rgba(14,28,23,.75);color:#fff;}
    .poster-card__status.status-cancelled{background:rgba(180,80,44,.9);color:#fff;}
    .poster-card__status.status-finished{background:rgba(91,107,100,.88);color:#fff;}
    .poster-card__status.inactive-flag{
        position:absolute;top:10px;right:10px;background:rgba(14,28,23,.75);color:#fff;
        font-size:10px;font-weight:800;padding:4px 8px;border-radius:999px;
    }
    .poster-card__body{padding:14px 16px;display:flex;flex-direction:column;gap:6px;flex:1;}
    .poster-card__title{font-size:14.5px;font-weight:800;}
    .poster-card__meta{font-size:12px;color:var(--ink-600);font-weight:600;}
    .poster-card__desc{font-size:12.5px;color:var(--ink-400);line-height:1.5;
        display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
    .poster-card__actions{display:flex;gap:8px;margin-top:auto;padding-top:10px;}
    .poster-card__actions button{
        flex:1;padding:8px 0;border-radius:9px;font-size:12.5px;font-weight:700;cursor:pointer;
        border:1px solid var(--line);background:#fff;color:var(--ink-900);
    }
    .poster-card__actions button.danger{color:var(--orange-500);border-color:rgba(239,141,61,.3);}
    .poster-card__actions button:hover{background:var(--green-050);}

    /* wider form modal, built on the layout's .modal-overlay / .modal-box */
    .modal-box.form-box{max-width:480px;text-align:left;}
    .form-box .modal-title{margin-bottom:4px;}
    .form-box .modal-sub{font-size:12.5px;color:var(--ink-400);margin-bottom:18px;}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;max-height:56vh;overflow-y:auto;padding-right:2px;}
    .form-grid .full{grid-column:1 / -1;}
    .form-field label{
        display:block;font-size:12px;font-weight:700;color:var(--ink-600);margin-bottom:6px;
    }
    .form-field input[type=text],
    .form-field input[type=date],
    .form-field input[type=number],
    .form-field input[type=file],
    .form-field select,
    .form-field textarea{
        width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:9px;
        font-family:inherit;font-size:13px;color:var(--ink-900);background:var(--green-050);
    }
    .form-field textarea{resize:vertical;min-height:64px;}
    .form-field .check-row{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;}
    .current-thumb{
        width:100%;height:90px;border-radius:9px;background-size:cover;background-position:center;
        background-color:var(--green-100);margin-bottom:8px;
    }
    .field-error{color:var(--orange-500);font-size:11px;font-weight:700;margin-top:4px;}
</style>
@endpush

@section('content')

@if(session('status'))
    <div class="status-note">{{ session('status') }}</div>
@endif

<div class="poster-toolbar">
    <button type="button" class="btn-primary" onclick="openModal('addPosterModal')">+ Add Poster</button>
</div>

<div class="card">
    <div class="card-head">
        <div class="card-title">Landing Page & Client Dashboard Carousel</div>
        <div class="dropdown-chip">{{ $posters->count() }} total</div>
    </div>

    @if($posters->isEmpty())
        <div class="empty-state">
            <div class="e-icon">🖼</div>
            <div class="e-title">No posters yet</div>
            <div class="e-sub">Add one and it'll show up in the hero carousel and the client dashboard.</div>
        </div>
    @else
        <div class="poster-grid">
            @foreach($posters as $poster)
                <div class="poster-card">
                    <div class="poster-card__img" style="background-image:url('{{ $poster->image_path ? asset('storage/'.$poster->image_path) : '' }}')">
                        <span class="poster-card__status status-{{ $poster->status }}">{{ $poster->status }}</span>
                        @if(!$poster->is_active)
                            <span class="poster-card__status inactive-flag">Hidden</span>
                        @endif
                    </div>
                    <div class="poster-card__body">
                        <div class="poster-card__title">{{ $poster->title }}</div>
                        <div class="poster-card__meta">
                            @if($poster->event_date) 📅 {{ $poster->event_date->format('M j, Y') }} @endif
                            @if($poster->event_time) · {{ $poster->event_time }} @endif
                        </div>
                        @if($poster->unit)
                            <div class="poster-card__meta">📍 {{ $poster->unit }}</div>
                        @endif
                        @if($poster->description)
                            <div class="poster-card__desc">{{ $poster->description }}</div>
                        @endif

                        <div class="poster-card__actions">
                            <button type="button" onclick="openModal('editPosterModal{{ $poster->id }}')">Edit</button>
                            <button type="button" class="danger" onclick="confirmDeletePoster({{ $poster->id }})">Delete</button>
                        </div>
                    </div>
                </div>

                <form id="deletePosterForm{{ $poster->id }}" method="POST" action="{{ route('admin.posters.destroy', $poster->id) }}" style="display:none;">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach
        </div>
    @endif
</div>

{{-- ---------- Add Poster modal ---------- --}}
<div class="modal-overlay" id="addPosterModal">
    <div class="modal-box form-box">
        <div class="modal-icon">🖼</div>
        <div class="modal-title">Add a poster</div>
        <div class="modal-sub">This will appear as a new slide in the carousel once saved.</div>

        <form method="POST" action="{{ route('admin.posters.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-grid">
                <div class="form-field full">
                    <label>Poster image</label>
                    <input type="file" name="image" accept="image/*" required>
                </div>
                <div class="form-field full">
                    <label>Title</label>
                    <input type="text" name="title" placeholder="e.g. Zumba Night" required>
                </div>
                <div class="form-field">
                    <label>Date</label>
                    <input type="date" name="event_date">
                </div>
                <div class="form-field">
                    <label>Time</label>
                    <input type="text" name="event_time" placeholder="6:00 PM – 8:00 PM">
                </div>
                <div class="form-field">
                    <label>Unit</label>
                    <select name="unit">
                        <option value="">— None —</option>
                        <option>Function 1</option>
                        <option>Function 2</option>
                        <option>Function 3</option>
                        <option>Lobby and Whole Court</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="open">Open</option>
                        <option value="full">Full</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="finished">Finished</option>
                    </select>
                </div>
                <div class="form-field full">
                    <label>Description</label>
                    <textarea name="description" placeholder="Short blurb shown on the back of the poster card"></textarea>
                </div>
                <div class="form-field">
                    <label>Sort order</label>
                    <input type="number" name="sort_order" value="0" min="0">
                </div>
                <div class="form-field" style="display:flex;align-items:end;">
                    <label class="check-row"><input type="checkbox" name="is_active" value="1" checked> Show in carousel</label>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('addPosterModal')">Cancel</button>
                <button type="submit" class="btn-logout" style="background:var(--green-600);border-color:var(--green-600);">Save Poster</button>
            </div>
        </form>
    </div>
</div>

{{-- ---------- Edit Poster modals (one per poster) ---------- --}}
@foreach($posters as $poster)
    <div class="modal-overlay" id="editPosterModal{{ $poster->id }}">
        <div class="modal-box form-box">
            <div class="modal-icon">🖼</div>
            <div class="modal-title">Edit poster</div>
            <div class="modal-sub">Leave the image field empty to keep the current poster image.</div>

            <form method="POST" action="{{ route('admin.posters.update', $poster->id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <div class="form-field full">
                        @if($poster->image_path)
                            <div class="current-thumb" style="background-image:url('{{ asset('storage/'.$poster->image_path) }}')"></div>
                        @endif
                        <label>Replace image (optional)</label>
                        <input type="file" name="image" accept="image/*">
                    </div>
                    <div class="form-field full">
                        <label>Title</label>
                        <input type="text" name="title" value="{{ $poster->title }}" required>
                    </div>
                    <div class="form-field">
                        <label>Date</label>
                        <input type="date" name="event_date" value="{{ $poster->event_date?->format('Y-m-d') }}">
                    </div>
                    <div class="form-field">
                        <label>Time</label>
                        <input type="text" name="event_time" value="{{ $poster->event_time }}" placeholder="6:00 PM – 8:00 PM">
                    </div>
                    <div class="form-field">
                        <label>Unit</label>
                        <select name="unit">
                            <option value="" {{ !$poster->unit ? 'selected' : '' }}>— None —</option>
                            @foreach(['Function 1','Function 2','Function 3','Lobby and Whole Court'] as $u)
                                <option {{ $poster->unit === $u ? 'selected' : '' }}>{{ $u }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Status</label>
                        <select name="status" required>
                            @foreach(['open' => 'Open', 'full' => 'Full', 'ongoing' => 'Ongoing', 'cancelled' => 'Cancelled', 'finished' => 'Finished'] as $val => $label)
                                <option value="{{ $val }}" {{ $poster->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field full">
                        <label>Description</label>
                        <textarea name="description">{{ $poster->description }}</textarea>
                    </div>
                    <div class="form-field">
                        <label>Sort order</label>
                        <input type="number" name="sort_order" value="{{ $poster->sort_order }}" min="0">
                    </div>
                    <div class="form-field" style="display:flex;align-items:end;">
                        <label class="check-row">
                            <input type="checkbox" name="is_active" value="1" {{ $poster->is_active ? 'checked' : '' }}> Show in carousel
                        </label>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal('editPosterModal{{ $poster->id }}')">Cancel</button>
                    <button type="submit" class="btn-logout" style="background:var(--green-600);border-color:var(--green-600);">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
@endforeach

@endsection

@push('scripts')
<script>
    function openModal(id){
        document.getElementById(id).classList.add('show');
    }
    function closeModal(id){
        document.getElementById(id).classList.remove('show');
    }
    // click outside any .modal-overlay closes it
    document.querySelectorAll('.modal-overlay').forEach(function(overlay){
        overlay.addEventListener('click', function(e){
            if (e.target === overlay) overlay.classList.remove('show');
        });
    });
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape'){
            document.querySelectorAll('.modal-overlay.show').forEach(function(o){ o.classList.remove('show'); });
        }
    });
    function confirmDeletePoster(id){
        if (confirm('Remove this poster? This can\'t be undone.')) {
            document.getElementById('deletePosterForm' + id).submit();
        }
    }
</script>
@endpush