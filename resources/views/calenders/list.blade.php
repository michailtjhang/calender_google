@extends('calenders.app')

@section('title', 'My Calendar')

@section('styles')
    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.css' rel='stylesheet' />

    <!-- DateTimePicker CSS -->
    <link rel="stylesheet" href="{{ asset('assets/datetimepicker/jquery.datetimepicker.css') }}">
@endsection

@section('content')
    <!-- Header Section -->
    <div class="mb-6 animate-slide-up">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold text-base-content mb-2">My Calendar</h1>
                <p class="text-base-content/70">Manage your events and schedules</p>
            </div>
            <div class="flex gap-2">
                @if ($hasCalendarAccess)
                    <a href="{{ route('sync-calender') }}" class="btn btn-primary gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Sync Calendar
                    </a>
                @else
                    <a href="{{ $OAuth2Client }}" class="btn btn-outline btn-primary gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 48 48">
                            <path fill="#FFC107"
                                d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z" />
                            <path fill="#FF3D00"
                                d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z" />
                            <path fill="#4CAF50"
                                d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z" />
                            <path fill="#1976D2"
                                d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z" />
                        </svg>
                        Connect Google
                    </a>
                @endif

            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 animate-slide-up" style="animation-delay: 0.2s;">
        <div class="stats shadow">
            <div class="stat">
                <div class="stat-figure text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        class="inline-block w-8 h-8 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="stat-title">Today's Events</div>
                <div class="stat-value text-primary">0</div>
                <div class="stat-desc">No events scheduled</div>
            </div>
        </div>

        <div class="stats shadow">
            <div class="stat">
                <div class="stat-figure text-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        class="inline-block w-8 h-8 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4">
                        </path>
                    </svg>
                </div>
                <div class="stat-title">This Week</div>
                <div class="stat-value text-secondary">0</div>
                <div class="stat-desc">Upcoming events</div>
            </div>
        </div>

        <div class="stats shadow">
            <div class="stat">
                <div class="stat-figure text-accent">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        class="inline-block w-8 h-8 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4">
                        </path>
                    </svg>
                </div>
                <div class="stat-title">Total Events</div>
                <div class="stat-value text-accent">0</div>
                <div class="stat-desc">All time</div>
            </div>
        </div>
    </div>

    <!-- Calendar Card -->
    <div class="card bg-base-100 shadow-xl animate-slide-up mt-4" style="animation-delay: 0.1s;">
        <div class="card-body p-4 md:p-8">
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Modal -->
    <dialog id="scheduleModal" class="modal">
        <div class="modal-box w-11/12 max-w-2xl">
            <form method="dialog">
                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
            </form>

            <h3 class="font-bold text-2xl mb-6 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
                Event Details
            </h3>

            <div class="space-y-4">
                <input type="hidden" id="eventId">

                <!-- Title -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Event Title</span>
                    </label>
                    <input type="text" id="title" placeholder="Enter event title" class="input input-bordered" />
                    <label class="label">
                        <span id="titleError" class="label-text-alt text-error"></span>
                    </label>
                </div>

                <!-- Description -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Description</span>
                    </label>
                    <textarea id="description" placeholder="Enter event description" class="textarea textarea-bordered h-24"></textarea>
                    <label class="label">
                        <span id="descriptionError" class="label-text-alt text-error"></span>
                    </label>
                </div>

                <!-- All Day Checkbox -->
                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-3">
                        <input type="checkbox" id="allDay" checked class="checkbox checkbox-primary" />
                        <span class="label-text font-semibold">All Day Event</span>
                    </label>
                </div>

                <!-- Date Inputs -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Start Date</span>
                        </label>
                        <input type="text" id="startDateTime" placeholder="Select start date"
                            class="input input-bordered" />
                        <label class="label">
                            <span id="startDateError" class="label-text-alt text-error"></span>
                        </label>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">End Date</span>
                        </label>
                        <input type="text" id="endDateTime" placeholder="Select end date"
                            class="input input-bordered" />
                        <label class="label">
                            <span id="endDateError" class="label-text-alt text-error"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="modal-action justify-between">
                <button type="button" id="deleteBtn" class="btn btn-error gap-2" style="display: none"
                    onclick="deleteEvent()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                    Delete
                </button>
                <div class="flex gap-2">
                    <form method="dialog">
                        <button type="button" class="btn btn-ghost">Cancel</button>
                    </form>
                    <button type="button" class="btn btn-primary gap-2" onclick="saveEvent()">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Save Event
                    </button>
                </div>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
@endsection

@section('scripts')
    <!-- Scripts -->
    <script src="{{ asset('assets/datetimepicker/jquery.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js'></script>
    <script src="{{ asset('assets/datetimepicker/build/jquery.datetimepicker.full.min.js') }}"></script>

    <script type="module">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const htmlElement = document.documentElement;

        themeToggle.addEventListener('change', function() {
            if (this.checked) {
                htmlElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            } else {
                htmlElement.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
            }
        });

        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        htmlElement.setAttribute('data-theme', savedTheme);
        if (savedTheme === 'dark') {
            themeToggle.checked = true;
        }
    </script>

    <script>
        var calendar = null;

        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                themeSystem: 'standard',
                events: '{{ route('refetch-calender') }}',
                dateClick: function(info) {
                    modalReset();

                    let startDate, endDate;
                    let allDay = $('#allDay').prop('checked');

                    if (allDay) {
                        startDate = moment(info.date).format('YYYY-MM-DD');
                        endDate = moment(info.date).format('YYYY-MM-DD');
                        initializeStartDateEndDateFormat('Y-m-d', true);
                    } else {
                        startDate = moment(info.date).format('YYYY-MM-DD HH:mm:ss');
                        endDate = moment(info.date).add(30, 'minutes').format('YYYY-MM-DD HH:mm:ss');
                        initializeStartDateEndDateFormat('Y-m-d H:i', false);
                    }

                    $('#startDateTime').val(startDate);
                    $('#endDateTime').val(endDate);

                    scheduleModal.showModal();
                },
                eventClick: function(info) {
                    modalReset();
                    const event = info.event;

                    $('#title').val(event.title);
                    $('#description').val(event.extendedProps.description || '');
                    $('#startDateTime').val(moment(event.start).format(event.allDay ? 'YYYY-MM-DD' :
                        'YYYY-MM-DD HH:mm:ss'));
                    $('#endDateTime').val(event.end ? moment(event.end).format(event.allDay ?
                        'YYYY-MM-DD' : 'YYYY-MM-DD HH:mm:ss') : moment(event.start).format(event
                        .allDay ? 'YYYY-MM-DD' : 'YYYY-MM-DD HH:mm:ss'));
                    $('#allDay').prop('checked', event.allDay);
                    $('#eventId').val(event.id);

                    scheduleModal.showModal();
                    $('#deleteBtn').show();

                    if (event.allDay) {
                        initializeStartDateEndDateFormat('Y-m-d', true);
                    } else {
                        initializeStartDateEndDateFormat('Y-m-d H:i', false);
                    }
                }
            });

            calendar.render();

            $('#allDay').change(function() {
                let is_all_day = $(this).is(':checked');

                if (is_all_day) {
                    let start_date = $('#startDateTime').val().slice(0, 10);
                    let end_date = $('#endDateTime').val().slice(0, 10);
                    $('#startDateTime').val(start_date);
                    $('#endDateTime').val(end_date);
                    initializeStartDateEndDateFormat('Y-m-d', is_all_day);
                } else {
                    let start_date = $('#startDateTime').val().slice(0, 10);
                    let end_date = $('#endDateTime').val().slice(0, 10);
                    $('#startDateTime').val(start_date + ' 12:00');
                    $('#endDateTime').val(end_date + ' 12:30');
                    initializeStartDateEndDateFormat('Y-m-d H:i', is_all_day);
                }
            });
        });

        function initializeStartDateEndDateFormat(format, allDay) {
            let timepicker = !allDay;

            $('#startDateTime').datetimepicker({
                format: format,
                timepicker: timepicker,
                onShow: function(ct) {
                    $('.xdsoft_datetimepicker').appendTo('#scheduleModal');
                }
            });

            $('#endDateTime').datetimepicker({
                format: format,
                timepicker: timepicker,
                onShow: function(ct) {
                    $('.xdsoft_datetimepicker').appendTo('#scheduleModal');
                }
            });
        }

        function modalReset() {
            $('#eventId').val('');
            $('#title').val('');
            $('#description').val('');
            $('#startDateTime').val('');
            $('#endDateTime').val('');
            $('#allDay').prop('checked', true);
            $('#deleteBtn').hide();
        }

        function saveEvent() {
            let eventId = $('#eventId').val();
            let url = '{{ route('calenders.store') }}';
            let postData = {
                start: $('#startDateTime').val(),
                end: $('#endDateTime').val(),
                title: $('#title').val(),
                description: $('#description').val(),
                is_all_day: $('#allDay').prop('checked') ? 1 : 0
            };

            if (postData.is_all_day) {
                postData.start = moment(postData.start).format('YYYY-MM-DD');
                postData.end = moment(postData.start).add(1, 'days').format('YYYY-MM-DD');
            } else {
                postData.start = moment(postData.start).format('YYYY-MM-DD HH:mm:ss');
                postData.end = moment(postData.end).format('YYYY-MM-DD HH:mm:ss');
            }

            if (eventId) {
                url = '{{ url('/calenders') }}' + '/' + eventId;
                postData._method = 'PUT';
            }

            $.ajax({
                url: url,
                type: 'POST',
                data: postData,
                success: function(response) {
                    if (response.status == 'success') {
                        scheduleModal.close();
                        calendar.refetchEvents();

                        // Show success toast
                        showToast('Event saved successfully!', 'success');
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr) {
                    showToast('Failed to save event', 'error');
                }
            });
        }

        function deleteEvent() {
            if (window.confirm('Are you sure you want to delete this event?')) {
                let eventId = $('#eventId').val();
                $.ajax({
                    url: '{{ url('/calenders') }}' + '/' + eventId,
                    type: 'DELETE',
                    success: function(response) {
                        if (response.status == 'success') {
                            scheduleModal.close();
                            calendar.refetchEvents();
                            showToast('Event deleted successfully!', 'success');
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr) {
                        showToast('Failed to delete event', 'error');
                    }
                });
            }
        }
    </script>
@endsection
