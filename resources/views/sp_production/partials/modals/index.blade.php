{{-- OPERATIONAL MODALS AGGREGATOR --}}

{{-- Modal 1: Log Good Output --}}
@include('sp_production.partials.modals.production')

{{-- Modal 2: Log Multi Defect Rejects --}}
@include('sp_production.partials.modals.reject')

{{-- Modal 3: Log Manual Downtime --}}
@include('sp_production.partials.modals.downtime')

{{-- Modal 4A: Cycle 1 — Issue Defects to Rework Bench --}}
@include('sp_production.partials.modals.issue_rework')

{{-- Modal 4B: Cycle 2 — Complete Rework Outcome --}}
@include('sp_production.partials.modals.complete_rework')

{{-- Modal 5: Receive Input Stock (WIP / Reworkable) --}}
@include('sp_production.partials.modals.input')

{{-- Modal 6: View Active Line Team Roster --}}
@include('sp_production.partials.modals.team_roster')

{{-- Modal 7: Add Line Team Member / Assign Role --}}
@include('sp_production.partials.modals.manpower')

{{-- Modal 8: Finish Production Session Confirmation --}}
@include('sp_production.partials.modals.finish')

{{-- Modal 9: Resume Downtime Reason Selection --}}
@include('sp_production.partials.modals.resume_downtime')
