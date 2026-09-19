@extends('layouts.client')

@section('title', 'New Reservation')

@push('styles')
<style>
    .wizard{max-width:760px;margin:0 auto;}
    .wizard-steps{display:flex;gap:8px;margin-bottom:22px;}
    .wizard-step-dot{
        flex:1;height:6px;border-radius:99px;background:rgba(255,255,255,.35);
        transition:background .2s ease;
    }
    .wizard-step-dot.active,.wizard-step-dot.done{background:var(--green-500);}

    .wizard-labels{display:flex;justify-content:space-between;margin-bottom:16px;font-size:11.5px;font-weight:700;color:#fff;text-shadow:0 1px 6px rgba(7,35,26,.4);}
    .wizard-labels span{flex:1;text-align:center;opacity:.6;}
    .wizard-labels span.active{opacity:1;}

    .step-panel{display:none;}
    .step-panel.active{display:block;}

    .field-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;}
    .field-row .full{grid-column:1 / -1;}
    @media (max-width:600px){ .field-row{grid-template-columns:1fr;} }

    .field label{display:block;font-size:12.5px;font-weight:700;color:var(--ink-600);margin-bottom:6px;}
    .field input[type=text],
    .field input[type=number],
    .field input[type=date],
    .field input[type=time],
    .field select{
        width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:10px;
        font-family:inherit;font-size:13.5px;color:var(--ink-900);background:rgba(255,255,255,.7);
    }
    .field-error{color:var(--orange-500);font-size:11.5px;font-weight:700;margin-top:5px;}

    .facility-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:16px;}
    @media (max-width:600px){ .facility-grid{grid-template-columns:1fr;} }
    .facility-option{
        border:1px solid var(--line);border-radius:12px;padding:13px 14px;cursor:pointer;
        background:rgba(255,255,255,.55);transition:border-color .15s ease, background .15s ease;
    }
    .facility-option.selected{border-color:var(--green-600);background:rgba(232,247,239,.85);}
    .facility-option .f-name{font-weight:800;font-size:13.5px;}
    .facility-option .f-cap{font-size:11.5px;color:var(--ink-400);margin-top:2px;}

    .aircon-toggle{display:flex;gap:10px;margin-bottom:14px;}
    .aircon-toggle label{
        flex:1;text-align:center;padding:9px 0;border:1px solid var(--line);border-radius:10px;
        font-size:12.5px;font-weight:700;cursor:pointer;background:rgba(255,255,255,.55);
    }
    .aircon-toggle input{display:none;}
    .aircon-toggle input:checked + span{color:var(--green-700);}
    .aircon-toggle label:has(input:checked){border-color:var(--green-600);background:rgba(232,247,239,.85);}

    .rate-modes{display:flex;flex-direction:column;gap:8px;margin-bottom:16px;}
    .rate-mode{
        display:flex;align-items:center;justify-content:space-between;gap:10px;
        padding:11px 14px;border:1px solid var(--line);border-radius:10px;cursor:pointer;
        background:rgba(255,255,255,.55);
    }
    .rate-mode.selected{border-color:var(--green-600);background:rgba(232,247,239,.85);}
    .rate-mode.disabled{opacity:.4;cursor:not-allowed;}
    .rate-mode .rm-label{font-size:13px;font-weight:700;}
    .rate-mode .rm-price{font-size:12.5px;font-weight:700;color:var(--green-700);}

    .summary-box{
        background:rgba(255,255,255,.6);border:1px solid var(--line);border-radius:12px;
        padding:14px 16px;margin-top:6px;
    }
    .summary-row{display:flex;justify-content:space-between;font-size:13px;padding:5px 0;}
    .summary-row.total{font-weight:800;font-size:15px;border-top:1px dashed var(--line);margin-top:6px;padding-top:10px;}
    .summary-row .muted-label{color:var(--ink-400);}

    .note-chip{
        background:var(--orange-100);color:#a85b1f;border:1px solid rgba(239,141,61,.35);
        border-radius:10px;padding:9px 12px;font-size:12px;font-weight:600;margin-top:10px;
    }

    .commitment-box{
        max-height:280px;overflow-y:auto;background:rgba(255,255,255,.65);border:1px solid var(--line);
        border-radius:12px;padding:16px 18px;font-size:12.5px;line-height:1.65;color:var(--ink-900);
    }
    .commitment-box h4{font-size:13px;margin:14px 0 6px;}
    .commitment-box h4:first-child{margin-top:0;}
    .commitment-box ul{margin:0 0 8px 18px;padding:0;}
    .commitment-box li{margin-bottom:4px;}
    .ack-row{
        display:flex;align-items:flex-start;gap:10px;margin-top:16px;padding:13px 14px;
        background:rgba(255,255,255,.6);border:1px solid var(--line);border-radius:10px;
    }
    .ack-row input{margin-top:3px;}
    .ack-row label{font-size:12.5px;font-weight:600;line-height:1.5;}

    .wizard-nav{display:flex;justify-content:space-between;margin-top:20px;}
    .wizard-nav button{
        padding:11px 22px;border-radius:11px;font-weight:700;font-size:13.5px;cursor:pointer;
        border:1px solid var(--line);background:rgba(255,255,255,.7);color:var(--ink-900);
    }
    .wizard-nav button.primary{background:var(--green-600);border-color:var(--green-600);color:#fff;}
    .wizard-nav button.primary:disabled{background:var(--ink-400);border-color:var(--ink-400);cursor:not-allowed;}
    .wizard-nav button:only-child{margin-left:auto;}
</style>
@endpush

@section('content')
<div class="wizard">

    @if($errors->any())
        <div class="note-chip" style="margin-bottom:16px;">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="wizard-steps">
        <div class="wizard-step-dot active" data-dot="1"></div>
        <div class="wizard-step-dot" data-dot="2"></div>
        <div class="wizard-step-dot" data-dot="3"></div>
        <div class="wizard-step-dot" data-dot="4"></div>
    </div>
    <div class="wizard-labels">
        <span class="active" data-label="1">Person Details</span>
        <span data-label="2">Room & Billing</span>
        <span data-label="3">Payment</span>
        <span data-label="4">Commitment Form</span>
    </div>

    <form method="POST" action="{{ route('client.reservations.store') }}" id="reservationForm">
        @csrf

        {{-- ================= STEP 1: PERSON DETAILS ================= --}}
        <div class="step-panel active card" data-step="1">
            <div class="card-title" style="margin-bottom:16px;">Who's this reservation for?</div>

            <div class="field-row">
                <div class="field full">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="{{ old('full_name', auth()->user()->first_name . ' ' . auth()->user()->last_name ?? '') }}" required>
                </div>
                <div class="field">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" value="{{ old('contact_number') }}" placeholder="09XX XXX XXXX" required>
                </div>
                <div class="field">
                    <label>Barangay</label>
                    <input type="text" name="barangay" value="{{ old('barangay') }}" required>
                </div>
                <div class="field full">
                    <label>Event / Activity Name</label>
                    <input type="text" name="activity_title" value="{{ old('activity_title') }}" placeholder="e.g. Family Wedding Reception" required>
                </div>
                <div class="field">
                    <label>Expected Attendees</label>
                    <input type="number" name="expected_attendees" min="1" value="{{ old('expected_attendees') }}">
                </div>
            </div>
        </div>

        {{-- ================= STEP 2: ROOM & BILLING ================= --}}
        <div class="step-panel card" data-step="2">
            <div class="card-title" style="margin-bottom:16px;">Pick a room & mode of rent</div>

            <div class="facility-grid" id="facilityGrid"></div>
            <input type="hidden" name="facility_id" id="facilityIdInput">

            <div class="aircon-toggle" id="airconToggle" style="display:none;">
                <label><input type="radio" name="aircon" value="1"><span>With aircon</span></label>
                <label><input type="radio" name="aircon" value="0"><span>Without aircon</span></label>
            </div>

            <div class="field-row">
                <div class="field">
                    <label>Event Date</label>
                    <input type="date" name="event_date" id="eventDate" value="{{ old('event_date') }}" required>
                </div>
                <div class="field"></div>
                <div class="field">
                    <label>Start Time</label>
                    <input type="time" name="start_time" id="startTime" value="{{ old('start_time') }}" required>
                </div>
                <div class="field">
                    <label>End Time</label>
                    <input type="time" name="end_time" id="endTime" value="{{ old('end_time') }}" required>
                </div>
            </div>

            <div class="rate-modes" id="rateModes"></div>

            <div class="summary-box">
                <div class="summary-row"><span class="muted-label">Room rate</span><span id="sumBase">₱0.00</span></div>
                <div class="summary-row"><span class="muted-label">Ingress (before event)</span><span id="sumIngressBefore">₱0.00</span></div>
                <div class="summary-row"><span class="muted-label">Ingress (after event)</span><span id="sumIngressAfter">₱0.00</span></div>
                <div class="summary-row total"><span>Total</span><span id="sumTotal">₱0.00</span></div>
                <div class="note-chip">Ingress fees aren't priced yet — currently ₱0, will be added here once confirmed. A 10% additional fee applies if you reschedule this reservation.</div>
            </div>
        </div>

        {{-- ================= STEP 3: PAYMENT ================= --}}
        <div class="step-panel card" data-step="3">
            <div class="card-title" style="margin-bottom:16px;">Downpayment</div>

            <div class="summary-box">
                <div class="summary-row"><span class="muted-label">Total reservation cost</span><span id="paySumTotal">₱0.00</span></div>
                <div class="summary-row total"><span>Downpayment ({{ $downpaymentPercent }}%)</span><span id="paySumDownpayment">₱0.00</span></div>
                <div class="summary-row"><span class="muted-label">Remaining balance</span><span id="paySumBalance">₱0.00</span></div>
            </div>

            <div class="note-chip">
                GCash payment collection isn't wired up in the system yet — for now, send your {{ $downpaymentPercent }}% downpayment via GCash and enter the reference number below. Staff will verify it manually.
            </div>

            <div class="field-row" style="margin-top:14px;">
                <div class="field full">
                    <label>GCash Reference Number</label>
                    <input type="text" name="gcash_reference" value="{{ old('gcash_reference') }}" placeholder="e.g. 1234567890123">
                </div>
            </div>
        </div>

        {{-- ================= STEP 4: COMMITMENT FORM ================= --}}
        <div class="step-panel card" data-step="4">
            <div class="card-title" style="margin-bottom:16px;">Digital Commitment Form</div>

            <div class="commitment-box">
                <h4>1. Facility Use — Dos</h4>
                <ul>
                    <li>Use the facility strictly for the activity declared on this reservation.</li>
                    <li>Arrive and vacate within your reserved time window; overstaying may incur additional per-hour charges.</li>
                    <li>Keep the facility clean and report any pre-existing damage before your event begins.</li>
                    <li>Ensure your event organizer/representative is present and reachable throughout the event.</li>
                    <li>Coordinate with on-site SSC staff for setup, equipment, and safety requirements.</li>
                </ul>

                <h4>2. Facility Use — Don'ts</h4>
                <ul>
                    <li>No use of open flame, fireworks, or confetti cannons inside any facility without prior written approval.</li>
                    <li>No smoking or use of prohibited substances anywhere within the complex.</li>
                    <li>No subleasing, reselling, or transferring your reservation to another individual or group.</li>
                    <li>No exceeding the facility's declared safe capacity.</li>
                    <li>No nailing, taping, or otherwise damaging walls, floors, fixtures, or equipment.</li>
                </ul>

                <h4>3. Payment & Downpayment</h4>
                <ul>
                    <li>A downpayment of <strong>{{ $downpaymentPercent }}%</strong> of the total cost is required to confirm this reservation.</li>
                    <li>The remaining balance is due on or before the event date, as coordinated with SSC staff.</li>
                    <li>Ingress fees (before/after the event) are currently unpriced and will be communicated separately once confirmed by the Municipality.</li>
                </ul>

                <h4>4. Cancellation Policy</h4>
                <ul>
                    <li>Cancellations are <strong>non-refundable</strong> — {{ $cancellationRefundPercent }}% of any amount paid will be returned.</li>
                    <li>This applies to both the downpayment and any additional payments already made.</li>
                </ul>

                <h4>5. Rescheduling Policy</h4>
                <ul>
                    <li>Rescheduling this reservation to a new date incurs an <strong>additional {{ $reschedulingFeePercent }}%</strong> fee on top of the total reservation cost.</li>
                    <li>Rescheduling is subject to the new date's availability and is not guaranteed.</li>
                    <li>Repeated rescheduling requests may be declined at SSC's discretion.</li>
                </ul>

                <h4>6. Damages & Liability</h4>
                <ul>
                    <li>The reserving individual/organization is liable for any damage to SSC property occurring during their reserved time window.</li>
                    <li>Damage assessments and any resulting charges are handled separately from this reservation's payment.</li>
                </ul>

                <p style="margin-top:14px;color:var(--ink-400);font-size:11.5px;">Commitment form version {{ $commitmentFormVersion }}. This content is a starting draft — SSC may revise these terms.</p>
            </div>

            <div class="ack-row">
                <input type="checkbox" id="commitmentCheckbox" name="commitment_acknowledged" value="1" required>
                <label for="commitmentCheckbox">I have read, understood, and agree to the terms above — including the non-refundable cancellation policy and the 10% additional rescheduling fee.</label>
            </div>
        </div>

        <div class="wizard-nav">
            <button type="button" id="backBtn" style="display:none;">Back</button>
            <button type="button" id="nextBtn" class="primary">Next</button>
            <button type="submit" id="submitBtn" class="primary" style="display:none;" disabled>Submit Reservation</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const FACILITIES = @json($facilities);
    const RATE_LABELS = {
        first_3_hours: 'First 3 hours',
        succeeding_hour: 'Succeeding hour',
        whole_day: 'Whole day (8am–5pm)',
        per_hour: 'Per hour',
    };

    let currentStep = 1;
    const totalSteps = 4;
    let selectedFacility = null;
    let selectedRateType = null;

    const dots = document.querySelectorAll('.wizard-step-dot');
    const labels = document.querySelectorAll('.wizard-labels span');
    const panels = document.querySelectorAll('.step-panel');
    const backBtn = document.getElementById('backBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');

    function showStep(step) {
        panels.forEach(p => p.classList.toggle('active', +p.dataset.step === step));
        dots.forEach(d => {
            const n = +d.dataset.dot;
            d.classList.toggle('active', n === step);
            d.classList.toggle('done', n < step);
        });
        labels.forEach(l => l.classList.toggle('active', +l.dataset.label === step));

        backBtn.style.display = step === 1 ? 'none' : 'inline-block';
        nextBtn.style.display = step === totalSteps ? 'none' : 'inline-block';
        submitBtn.style.display = step === totalSteps ? 'inline-block' : 'none';

        if (step === 3) updatePaymentSummary();
    }

    function validateStep(step) {
        const panel = document.querySelector(`.step-panel[data-step="${step}"]`);
        const inputs = panel.querySelectorAll('input[required], select[required]');
        for (const input of inputs) {
            if (!input.reportValidity()) return false;
        }
        if (step === 2 && (!selectedFacility || !selectedRateType)) {
            alert('Please select a facility and a mode of rent.');
            return false;
        }
        return true;
    }

    backBtn.addEventListener('click', () => { currentStep--; showStep(currentStep); });
    nextBtn.addEventListener('click', () => {
        if (!validateStep(currentStep)) return;
        currentStep++;
        showStep(currentStep);
    });

    document.getElementById('commitmentCheckbox').addEventListener('change', function () {
        submitBtn.disabled = !this.checked;
    });

    // ---------------- facility grid ----------------
    const facilityGrid = document.getElementById('facilityGrid');
    const facilityIdInput = document.getElementById('facilityIdInput');
    const airconToggle = document.getElementById('airconToggle');

    FACILITIES.forEach(f => {
        const el = document.createElement('div');
        el.className = 'facility-option';
        el.innerHTML = `<div class="f-name">${f.name}</div><div class="f-cap">Capacity: ${f.capacity} pax</div>`;
        el.addEventListener('click', () => selectFacility(f, el));
        facilityGrid.appendChild(el);
    });

    function selectFacility(facility, el) {
        selectedFacility = facility;
        selectedRateType = null;
        facilityIdInput.value = facility.id;

        document.querySelectorAll('.facility-option').forEach(o => o.classList.remove('selected'));
        el.classList.add('selected');

        const hasAirconVariant = Object.values(facility.schedule).some(modes => 'aircon' in modes || 'no_aircon' in modes);
        airconToggle.style.display = hasAirconVariant ? 'flex' : 'none';
        if (!hasAirconVariant) {
            airconToggle.querySelectorAll('input').forEach(i => i.checked = false);
        }

        renderRateModes();
    }

    // ---------------- rate modes ----------------
    const rateModesEl = document.getElementById('rateModes');

    function getAirconSelection() {
        const checked = airconToggle.querySelector('input:checked');
        return checked ? checked.value === '1' : null;
    }

    function rateFor(type) {
        if (!selectedFacility || !selectedFacility.schedule[type]) return null;
        const modes = selectedFacility.schedule[type];
        if ('default' in modes) return modes.default;
        const aircon = getAirconSelection();
        if (aircon === null) return null;
        return aircon ? (modes.aircon ?? null) : (modes.no_aircon ?? null);
    }

    function renderRateModes() {
        rateModesEl.innerHTML = '';
        if (!selectedFacility) return;

        Object.keys(RATE_LABELS).forEach(type => {
            if (!selectedFacility.schedule[type]) return; // not offered for this facility

            const row = document.createElement('div');
            row.className = 'rate-mode';
            row.dataset.type = type;
            row.innerHTML = `<span class="rm-label">${RATE_LABELS[type]}</span><span class="rm-price">${formatRatePreview(type)}</span>`;
            row.addEventListener('click', () => {
                selectedRateType = type;
                document.querySelectorAll('.rate-mode').forEach(r => r.classList.remove('selected'));
                row.classList.add('selected');
                updateBillingSummary();
            });
            rateModesEl.appendChild(row);
        });
    }

    function formatRatePreview(type) {
        const modes = selectedFacility.schedule[type];
        if ('default' in modes) {
            return type === 'whole_day' || type === 'first_3_hours' ? peso(modes.default) : peso(modes.default) + '/hr';
        }
        return `${peso(modes.aircon ?? 0)}/hr (aircon) · ${peso(modes.no_aircon ?? 0)}/hr (no aircon)`;
    }

    function peso(n) { return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2 }); }

    airconToggle.addEventListener('change', () => { renderRateModes(); updateBillingSummary(); });
    document.getElementById('startTime').addEventListener('change', updateBillingSummary);
    document.getElementById('endTime').addEventListener('change', updateBillingSummary);

    function computeHours() {
        const s = document.getElementById('startTime').value;
        const e = document.getElementById('endTime').value;
        if (!s || !e) return 0;
        const [sh, sm] = s.split(':').map(Number);
        const [eh, em] = e.split(':').map(Number);
        return Math.max(0, (eh * 60 + em - (sh * 60 + sm)) / 60);
    }

    function computeBaseAmount() {
        if (!selectedFacility || !selectedRateType) return 0;
        const hours = computeHours();

        if (selectedRateType === 'whole_day') return rateFor('whole_day') ?? 0;
        if (selectedRateType === 'per_hour') return (rateFor('per_hour') ?? 0) * hours;
        if (selectedRateType === 'succeeding_hour') return (rateFor('succeeding_hour') ?? 0) * hours;
        if (selectedRateType === 'first_3_hours') {
            const base = rateFor('first_3_hours') ?? 0;
            const extra = Math.max(0, hours - 3);
            return base + extra * (rateFor('succeeding_hour') ?? 0);
        }
        return 0;
    }

    function updateBillingSummary() {
        const base = computeBaseAmount();
        document.getElementById('sumBase').textContent = peso(base);
        document.getElementById('sumIngressBefore').textContent = peso(0);
        document.getElementById('sumIngressAfter').textContent = peso(0);
        document.getElementById('sumTotal').textContent = peso(base);
    }

    function updatePaymentSummary() {
        const total = computeBaseAmount();
        const downpayment = Math.round(total * {{ $downpaymentPercent }} / 100 * 100) / 100;
        document.getElementById('paySumTotal').textContent = peso(total);
        document.getElementById('paySumDownpayment').textContent = peso(downpayment);
        document.getElementById('paySumBalance').textContent = peso(total - downpayment);
    }

    showStep(1);
</script>
@endpush