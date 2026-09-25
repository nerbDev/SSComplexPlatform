@extends('layouts.client')

@section('title', 'New Reservation')

@push('styles')
<style>
    .wizard{max-width:780px;margin:0 auto;}
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

    /* ---------- booking type ---------- */
    .type-grid{display:grid;grid-template-columns:repeat(3, 1fr);gap:14px;margin-bottom:18px;}
    @media (max-width:820px){ .type-grid{grid-template-columns:1fr 1fr;} }
    @media (max-width:600px){ .type-grid{grid-template-columns:1fr;} }
    .type-option{
        border:1px solid var(--line);border-radius:14px;padding:16px;cursor:pointer;
        background:rgba(255,255,255,.55);transition:border-color .15s ease, background .15s ease;
    }
    .type-option.selected{border-color:var(--green-600);background:rgba(232,247,239,.85);}
    .type-option .t-name{font-weight:800;font-size:14px;margin-bottom:6px;}
    .type-option .t-desc{font-size:12px;color:var(--ink-600);line-height:1.55;}
    .type-option .t-desc li{margin-bottom:3px;}
    .type-option .t-desc ul{margin:0;padding-left:16px;}

    /* ---------- facility cards ---------- */
    .facility-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:16px;}
    @media (max-width:600px){ .facility-grid{grid-template-columns:1fr;} }
    .facility-option{
        border:1px solid var(--line);border-radius:12px;padding:13px 14px;cursor:pointer;
        background:rgba(255,255,255,.55);transition:border-color .15s ease, background .15s ease;
        position:relative;
    }
    .facility-option.selected{border-color:var(--green-600);background:rgba(232,247,239,.85);}
    .facility-option .f-name{font-weight:800;font-size:13.5px;padding-right:70px;}
    .facility-option .f-cap{font-size:11.5px;color:var(--ink-400);margin-top:2px;}
    .see-details-btn{
        position:absolute;top:12px;right:12px;font-size:11px;font-weight:700;
        padding:5px 10px;border-radius:999px;border:1px solid var(--green-600);
        color:var(--green-700);background:#fff;cursor:pointer;
    }
    .see-details-btn:hover{background:var(--green-050);}

    /* ---------- room details modal ---------- */
    .room-modal-photo{
        width:100%;height:170px;border-radius:12px;background-size:cover;background-position:center;
        background-color:var(--green-100);margin-bottom:14px;
    }
    .room-modal-row{display:flex;justify-content:space-between;font-size:13px;padding:6px 0;border-bottom:1px solid var(--line);}
    .room-modal-row:last-child{border-bottom:none;}
    .room-modal-row .rm-label{color:var(--ink-400);font-weight:600;}
    .room-modal-row .rm-value{font-weight:700;}

    .aircon-toggle{display:flex;gap:10px;margin-bottom:14px;}
    .aircon-toggle label{
        flex:1;text-align:center;padding:9px 0;border:1px solid var(--line);border-radius:10px;
        font-size:12.5px;font-weight:700;cursor:pointer;background:rgba(255,255,255,.55);
    }
    .aircon-toggle input{display:none;}
    .aircon-toggle label:has(input:checked){border-color:var(--green-600);background:rgba(232,247,239,.85);}

    .rate-modes{display:flex;flex-direction:column;gap:8px;margin-bottom:16px;}
    .rate-mode{
        display:flex;align-items:center;justify-content:space-between;gap:10px;
        padding:11px 14px;border:1px solid var(--line);border-radius:10px;cursor:pointer;
        background:rgba(255,255,255,.55);
    }
    .rate-mode.selected{border-color:var(--green-600);background:rgba(232,247,239,.85);}
    .rate-mode .rm-label{font-size:13px;font-weight:700;}
    .rate-mode .rm-price{font-size:12.5px;font-weight:700;color:var(--green-700);}

    /* ---------- availability panel ---------- */
    .availability-panel{
        border-radius:12px;padding:12px 14px;margin-bottom:16px;font-size:12.5px;
        border:1px solid var(--line);background:rgba(255,255,255,.55);display:none;
    }
    .availability-panel.show{display:block;}
    .availability-panel.blocked{background:rgba(180,80,44,.1);border-color:rgba(180,80,44,.35);color:#a04a26;}
    .availability-panel .av-title{font-weight:700;margin-bottom:6px;}
    .busy-chip{
        display:inline-block;margin:3px 6px 0 0;padding:4px 9px;border-radius:999px;
        background:var(--orange-100);color:#a85b1f;font-weight:700;font-size:11.5px;
    }
    .availability-panel.ok{background:rgba(31,174,116,.08);border-color:rgba(31,174,116,.3);color:var(--green-700);}

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
    .commitment-box [data-type-block]{display:none;}
    .commitment-box [data-type-block].show{display:block;}

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

    /* generic modal (room details) */
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(7,35,26,.55);align-items:center;justify-content:center;z-index:300;padding:20px;}
    .modal-overlay.show{display:flex;}
    .modal-box{background:#fff;border-radius:var(--radius-lg);width:100%;max-width:400px;padding:22px;box-shadow:0 24px 60px -20px rgba(7,35,26,.4);}
    .modal-box .modal-title{font-size:16px;font-weight:800;margin-bottom:14px;}
    .modal-close-btn{width:100%;margin-top:16px;padding:11px 0;border-radius:11px;font-weight:700;font-size:13.5px;cursor:pointer;border:1px solid var(--line);background:var(--green-050);}
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
    </div>
    <div class="wizard-labels">
        <span class="active" data-label="1">Person Details</span>
        <span data-label="2">Room & Billing</span>
        <span data-label="3">Commitment Form</span>
    </div>

    <form method="POST" action="{{ route('client.reservations.store') }}" id="reservationForm">
        @csrf

        {{-- ================= STEP 1: PERSON DETAILS + BOOKING TYPE ================= --}}
        <div class="step-panel active card" data-step="1">
            <div class="card-title" style="margin-bottom:12px;">What kind of booking is this?</div>

            <div class="type-grid">
                <div class="type-option" data-type="appointment">
                    <div class="t-name">Appointment</div>
                    <div class="t-desc">
                        <ul>
                            <li>Full payment upfront</li>
                            <li>10% fee if rescheduled</li>
                            <li>Ingress fees apply</li>
                        </ul>
                    </div>
                </div>
                <div class="type-option" data-type="room_reservation">
                    <div class="t-name">Room Reservation</div>
                    <div class="t-desc">
                        <ul>
                            <li>For events booked far in advance</li>
                            <li>{{ $roomReservationDownpaymentPercent }}% downpayment now</li>
                            <li>Remaining balance due {{ $balanceDueDaysBefore }} days before the event</li>
                            <li>Same room rates, 10% rescheduling fee</li>
                        </ul>
                    </div>
                </div>
                <div class="type-option" data-type="free_use">
                    <div class="t-name">Free Use</div>
                    <div class="t-desc">
                        <ul>
                            <li>No payment required</li>
                            <li>Must be qualified for free use</li>
                            <li>Approval form from Subic Administration Office, with Mayor's approval</li>
                        </ul>
                    </div>
                </div>
            </div>
            <input type="hidden" name="booking_type" id="bookingTypeInput">

            <div class="card-title" style="margin-bottom:16px;">Who's this booking for?</div>

            <div class="field-row">
                <div class="field full">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="{{ old('full_name', trim((auth()->user()->first_name ?? '') . ' ' . (auth()->user()->last_name ?? ''))) }}" required>
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
            <input type="hidden" name="rate_type" id="rateTypeInput">

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

            <div class="availability-panel" id="availabilityPanel"></div>

            <div id="billingSection">
                <div class="rate-modes" id="rateModes"></div>

                <div class="summary-box">
                    <div class="summary-row"><span class="muted-label">Room rate</span><span id="sumBase">₱0.00</span></div>
                    <div class="summary-row"><span class="muted-label">Ingress (before event)</span><span id="sumIngressBefore">₱0.00</span></div>
                    <div class="summary-row"><span class="muted-label">Ingress (after event)</span><span id="sumIngressAfter">₱0.00</span></div>
                    <div class="summary-row total"><span>Total</span><span id="sumTotal">₱0.00</span></div>
                    <div class="note-chip">Ingress fees aren't priced yet — currently ₱0, will be added here once confirmed. A 10% additional fee applies if you reschedule.</div>
                </div>
            </div>

            <div class="note-chip" id="freeUseNote" style="display:none;">
                Free Use bookings don't require any payment — just pick a room, date, and time. Once Staff verifies your request, you'll be asked to attach your approval form.
            </div>
        </div>

        {{-- ================= STEP 3: COMMITMENT FORM ================= --}}
        <div class="step-panel card" data-step="3">
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

                <h4>3. Payment</h4>
                <div data-type-block="appointment">
                    <ul>
                        <li>Appointments require <strong>full payment upfront</strong> to confirm the booking.</li>
                        <li>Ingress fees (before/after the event) are currently unpriced and will be communicated separately once confirmed by the Municipality.</li>
                    </ul>
                </div>
                <div data-type-block="room_reservation">
                    <ul>
                        <li>Room Reservations require a <strong>{{ $roomReservationDownpaymentPercent }}% downpayment</strong> now to confirm the booking.</li>
                        <li>The remaining balance must be settled at least <strong>{{ $balanceDueDaysBefore }} days before the event date</strong>. Unpaid balances past this deadline may result in cancellation.</li>
                        <li>Ingress fees (before/after the event) are currently unpriced and will be communicated separately once confirmed by the Municipality.</li>
                    </ul>
                </div>
                <div data-type-block="free_use">
                    <ul>
                        <li><strong>No payment is required</strong> for Free Use bookings.</li>
                        <li>You must be qualified for free use of the facility.</li>
                        <li>An approval form from the Subic Administration Office, with the Mayor's approval, must be attached once Staff verifies this request.</li>
                    </ul>
                </div>

                <h4>4. Cancellation Policy</h4>
                <ul>
                    <li>Cancellations are <strong>non-refundable</strong> — {{ $cancellationRefundPercent }}% of any amount paid will be returned.</li>
                    <li>This applies to both the downpayment/full payment and any additional payments already made.</li>
                </ul>

                <h4>5. Rescheduling Policy</h4>
                <ul>
                    <li>Rescheduling to a new date incurs an <strong>additional {{ $reschedulingFeePercent }}%</strong> fee on top of the total cost.</li>
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
                <label for="commitmentCheckbox">I have read, understood, and agree to the terms above.</label>
            </div>
        </div>

        <div class="wizard-nav">
            <button type="button" id="backBtn" style="display:none;">Back</button>
            <button type="button" id="nextBtn" class="primary">Next</button>
            <button type="submit" id="submitBtn" class="primary" style="display:none;" disabled>Submit</button>
        </div>
    </form>
</div>

{{-- room details modal --}}
<div class="modal-overlay" id="roomDetailsModal">
    <div class="modal-box">
        <div class="modal-title" id="rmName">Room name</div>
        <div class="room-modal-photo" id="rmPhoto"></div>
        <div class="room-modal-row"><span class="rm-label">Max Capacity</span><span class="rm-value" id="rmCapacity">—</span></div>
        <div class="room-modal-row"><span class="rm-label">Location</span><span class="rm-value" id="rmLocation">—</span></div>
        <button type="button" class="modal-close-btn" onclick="closeModal('roomDetailsModal')">Close</button>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const FACILITIES = @json($facilities);
    const AVAILABILITY_URL = @json(route('client.reservations.availability'));
    const RATE_LABELS = {
        first_3_hours: 'First 3 hours',
        succeeding_hour: 'Succeeding hour',
        whole_day: 'Whole day (8am–5pm)',
        per_hour: 'Per hour',
    };

    function openModal(id){ document.getElementById(id).classList.add('show'); }
    function closeModal(id){ document.getElementById(id).classList.remove('show'); }
    document.querySelectorAll('.modal-overlay').forEach(o => o.addEventListener('click', e => { if (e.target === o) o.classList.remove('show'); }));

    let currentStep = 1;
    const totalSteps = 3;
    let selectedFacility = null;
    let selectedRateType = null;
    let selectedBookingType = null;
    let busyRanges = [];
    let wholeDayBlocked = false;

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

        if (step === 3) updateCommitmentBlocks();
    }

    function validateStep(step) {
        const panel = document.querySelector(`.step-panel[data-step="${step}"]`);
        const inputs = panel.querySelectorAll('input[required], select[required]');
        for (const input of inputs) {
            if (!input.reportValidity()) return false;
        }
        if (step === 1 && !selectedBookingType) {
            alert('Please choose Appointment, Room Reservation, or Free Use.');
            return false;
        }
        if (step === 2) {
            const isFreeUse = selectedBookingType === 'free_use';
            if (!selectedFacility || (!isFreeUse && !selectedRateType)) {
                alert(isFreeUse ? 'Please select a facility.' : 'Please select a facility and a mode of rent.');
                return false;
            }
            if (wholeDayBlocked) {
                alert('This facility is not available on the selected date.');
                return false;
            }
            if (hasTimeConflict()) {
                alert('Your selected time overlaps with an existing booking. Please choose a different time.');
                return false;
            }
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

    // ---------------- booking type ----------------
    document.querySelectorAll('.type-option').forEach(el => {
        el.addEventListener('click', () => {
            selectedBookingType = el.dataset.type;
            document.getElementById('bookingTypeInput').value = selectedBookingType;
            document.querySelectorAll('.type-option').forEach(o => o.classList.remove('selected'));
            el.classList.add('selected');
            updateStep2Visibility();
        });
    });

    // free_use skips billing entirely — Step 2 becomes just "pick a room, date, time"
    function updateStep2Visibility() {
        const isFreeUse = selectedBookingType === 'free_use';
        document.getElementById('billingSection').style.display = isFreeUse ? 'none' : 'block';
        document.getElementById('freeUseNote').style.display = isFreeUse ? 'block' : 'none';
        if (isFreeUse) {
            airconToggle.style.display = 'none';
        }
    }

    // ---------------- facility grid ----------------
    const facilityGrid = document.getElementById('facilityGrid');
    const facilityIdInput = document.getElementById('facilityIdInput');
    const airconToggle = document.getElementById('airconToggle');
    const facilityElements = {};

    FACILITIES.forEach(f => {
        const el = document.createElement('div');
        el.className = 'facility-option';
        el.innerHTML = `
            <button type="button" class="see-details-btn" data-details="${f.id}">See details</button>
            <div class="f-name">${f.name}</div>
            <div class="f-cap">Capacity: ${f.capacity} pax</div>
        `;
        el.querySelector('.f-name, .f-cap');
        el.addEventListener('click', (e) => {
            if (e.target.closest('.see-details-btn')) return; // handled separately
            selectFacility(f, el);
        });
        el.querySelector('.see-details-btn').addEventListener('click', (e) => {
            e.stopPropagation();
            showRoomDetails(f);
        });
        facilityGrid.appendChild(el);
        facilityElements[f.id] = el;
    });

    function showRoomDetails(f) {
        document.getElementById('rmName').textContent = f.name;
        document.getElementById('rmPhoto').style.backgroundImage = f.image ? `url('${f.image}')` : 'none';
        document.getElementById('rmCapacity').textContent = f.capacity + ' pax';
        document.getElementById('rmLocation').textContent = f.location || '—';
        openModal('roomDetailsModal');
    }

    function selectFacility(facility, el) {
        selectedFacility = facility;
        selectedRateType = null;
        facilityIdInput.value = facility.id;
        document.getElementById('rateTypeInput').value = '';

        document.querySelectorAll('.facility-option').forEach(o => o.classList.remove('selected'));
        el.classList.add('selected');

        const hasAirconVariant = Object.values(facility.schedule).some(modes => 'aircon' in modes || 'no_aircon' in modes);
        airconToggle.style.display = hasAirconVariant ? 'flex' : 'none';
        if (!hasAirconVariant) {
            airconToggle.querySelectorAll('input').forEach(i => i.checked = false);
        }

        updateStep2Visibility(); // re-applies free_use's "hide billing" override on top of the aircon check above

        renderRateModes();
        checkAvailability();
    }

    // ---------------- availability (non-conflict check) ----------------
    const availabilityPanel = document.getElementById('availabilityPanel');

    function checkAvailability() {
        const date = document.getElementById('eventDate').value;
        if (!selectedFacility || !date) {
            availabilityPanel.classList.remove('show');
            return;
        }

        fetch(`${AVAILABILITY_URL}?facility_id=${selectedFacility.id}&date=${date}`)
            .then(r => r.json())
            .then(data => {
                busyRanges = data.busy_ranges || [];
                wholeDayBlocked = !!data.whole_day_blocked;
                renderAvailabilityPanel(data);
                updateBillingSummary();
            })
            .catch(() => { availabilityPanel.classList.remove('show'); });
    }

    function renderAvailabilityPanel(data) {
        availabilityPanel.classList.add('show');
        availabilityPanel.classList.remove('blocked', 'ok');

        if (data.whole_day_blocked) {
            availabilityPanel.classList.add('blocked');
            availabilityPanel.innerHTML = `<div class="av-title">Not available on this date</div>${data.block_reason || 'This facility is closed on the selected date.'}`;
            return;
        }

        if (!data.busy_ranges || data.busy_ranges.length === 0) {
            availabilityPanel.classList.add('ok');
            availabilityPanel.innerHTML = `<div class="av-title">Fully open on this date</div>No existing bookings for ${selectedFacility.name} on this date.`;
            return;
        }

        const chips = data.busy_ranges.map(r => `<span class="busy-chip">${r.start}–${r.end}</span>`).join('');
        availabilityPanel.innerHTML = `<div class="av-title">Already booked on this date</div>${chips}<div style="margin-top:6px;">Pick a start/end time outside these ranges.</div>`;
    }

    function hasTimeConflict() {
        const start = document.getElementById('startTime').value;
        const end = document.getElementById('endTime').value;
        if (!start || !end) return false;
        return busyRanges.some(r => start < r.end && end > r.start);
    }

    document.getElementById('eventDate').addEventListener('change', checkAvailability);

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
            if (!selectedFacility.schedule[type]) return;

            const row = document.createElement('div');
            row.className = 'rate-mode';
            row.dataset.type = type;
            row.innerHTML = `<span class="rm-label">${RATE_LABELS[type]}</span><span class="rm-price">${formatRatePreview(type)}</span>`;
            row.addEventListener('click', () => {
                selectedRateType = type;
                document.getElementById('rateTypeInput').value = type;
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

    function updateCommitmentBlocks() {
        document.querySelectorAll('[data-type-block]').forEach(b => {
            b.classList.toggle('show', b.dataset.typeBlock === selectedBookingType);
        });
    }

    // ---------------- restore state after a validation error ----------------
    // Laravel's back()->withErrors()->withInput() reloads this page fresh —
    // without this, the client would lose their Step 2 room/rate/booking-type
    // picks (those only ever lived in JS state) and always land back on Step 1
    // regardless of which step the actual error was on.
    const OLD = {
        booking_type: @json(old('booking_type')),
        facility_id: @json(old('facility_id')),
        rate_type: @json(old('rate_type')),
        aircon: @json(old('aircon')),
    };
    const FIELD_ERRORS = @json($errors->keys());

    if (OLD.booking_type) {
        const typeEl = document.querySelector(`.type-option[data-type="${OLD.booking_type}"]`);
        if (typeEl) {
            selectedBookingType = OLD.booking_type;
            document.getElementById('bookingTypeInput').value = OLD.booking_type;
            typeEl.classList.add('selected');
            updateStep2Visibility();
        }
    }

    if (OLD.facility_id && facilityElements[OLD.facility_id]) {
        const facility = FACILITIES.find(f => String(f.id) === String(OLD.facility_id));
        if (facility) {
            selectFacility(facility, facilityElements[OLD.facility_id]);

            if (OLD.aircon !== null && OLD.aircon !== undefined) {
                const radio = airconToggle.querySelector(`input[value="${OLD.aircon}"]`);
                if (radio) { radio.checked = true; renderRateModes(); }
            }

            if (OLD.rate_type) {
                const rateEl = rateModesEl.querySelector(`.rate-mode[data-type="${OLD.rate_type}"]`);
                if (rateEl) {
                    selectedRateType = OLD.rate_type;
                    document.getElementById('rateTypeInput').value = OLD.rate_type;
                    rateEl.classList.add('selected');
                }
            }

            updateBillingSummary();
        }
    }

    // Jump to the earliest step that actually has a validation error,
    // instead of always resetting to Step 1.
    const STEP_1_FIELDS = ['booking_type', 'full_name', 'contact_number', 'barangay', 'activity_title', 'expected_attendees'];
    const STEP_2_FIELDS = ['facility_id', 'aircon', 'rate_type', 'event_date', 'start_time', 'end_time'];

    let startStep = 1;
    if (FIELD_ERRORS.some(f => STEP_2_FIELDS.includes(f))) {
        startStep = 2;
    } else if (FIELD_ERRORS.some(f => f === 'commitment_acknowledged')) {
        startStep = 3;
    } else if (FIELD_ERRORS.some(f => STEP_1_FIELDS.includes(f))) {
        startStep = 1;
    }

    showStep(startStep);
</script>
@endpush