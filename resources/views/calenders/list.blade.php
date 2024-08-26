@extends('calenders.app')

@section('content')
    <link rel="stylesheet" href="{{ asset('assets/datetimepicker/jquery.datetimepicker.css') }}">

    <div class="flex justify-center mx-72">
        <div class="w-full md:w-11/12">
            <div class="shadow-lg bg-base-100 rounded-lg">
                <div class="p-6">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <dialog id="scheduleModal" class="modal">
        <div class="modal-box w-11/12 max-w-5xl bg-white text-gray-800">
            <h3 class="font-bold text-lg text-gray-900">Add Schedule</h3>
            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2 text-gray-600"
                onclick="scheduleModal.close()">✕</button>

            <div class="py-4 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="grid grid-cols-1">
                        <div class="form-control">
                            <input type="hidden" id="eventId">
                            <label for="title" class="label text-gray-700">Title</label>
                            <input type="text" class="input input-bordered bg-white text-gray-800" id="title"
                                placeholder="Title" name="title">
                            <span id="titleError" class="text-error text-sm"></span>
                        </div>

                        <div class="form-control">
                            <div class="flex items-center">
                                <input type="checkbox" checked="checked" class="checkbox checkbox-primary mr-2"
                                    id="allDay" name="allDay" required />
                                <label for="allDay" class="label text-gray-700">All Day</label>
                            </div>
                        </div>

                        <div class="form-control">
                            <label for="description" class="label text-gray-700">Description</label>
                            <input type="text" class="input input-bordered bg-white text-gray-800" id="description"
                                placeholder="Enter Description" name="description">
                            <span id="descriptionError" class="text-error text-sm"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        <div class="form-control">
                            <label class="label text-gray-700">Start Date</label>
                            <input type="text" class="input input-bordered bg-white text-gray-800" id="startDateTime"
                                placeholder="Select Start Date" name="title">
                            <span id="startDateError" class="text-error text-sm"></span>
                        </div>
                        <div class="form-control">
                            <label class="label text-gray-700">End Date</label>
                            <input type="text" class="input input-bordered bg-white text-gray-800" id="endDateTime"
                                placeholder="Select End Date" name="title">
                            <span id="endDateError" class="text-error text-sm"></span>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-action">
                <button type="button" id="deleteBtn" class="btn btn-error" style="display: none"
                    onclick="deleteEvent()">Close</button>
                <button type="button" id="cancelBtn" class="btn" onclick="scheduleModal.close()">Close</button>
                <button type="button" id="saveBtn" class="btn btn-primary" onclick="saveEvent()">Save changes</button>
            </div>
        </div>
    </dialog>

    <script type="module">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>

    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js'></script>

    <script src="{{ asset('assets/datetimepicker/jquery.js') }}"></script>
    <script src="{{ asset('assets/datetimepicker/build/jquery.datetimepicker.full.min.js') }}"></script>
    <script>
        var calendar = null;

        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar')
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
                    let startDate, endDate; // Deklarasi variabel di luar blok if-else
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
                    modalReset();
                    scheduleModal.showModal();
                }
            })
            calendar.render()
            $('#allDay').change(function() {
                let is_all_day = $(this).is(':checked');

                if (is_all_day) {
                    let start_date = $('#startDateTime').val().slice(0, 10);
                    $('#endDateTime').val(start_date);
                    let end_date = $('#endDateTime').val().slice(0, 10);
                    $('#startDateTime').val(end_date);

                    initializeStartDateEndDateFormat('Y-m-d', is_all_day)
                } else {
                    let start_date = $('#startDateTime').val().slice(0, 10);
                    let end_date = $('#endDateTime').val().slice(0, 10);
                    $('#startDateTime').val(start_date + ' 12:00');
                    $('#endDateTime').val(end_date + ' 12:30');

                    initializeStartDateEndDateFormat('Y-m-d H:i', is_all_day)
                }
            })
        })

        function initializeStartDateEndDateFormat(format, allDay) {
            let timepicker = !allDay;

            $('#startDateTime').datetimepicker({
                format: format,
                timepicker: timepicker,
                onShow: function(ct) {
                    // Memindahkan datepicker ke dalam modal secara langsung
                    $('.xdsoft_datetimepicker').appendTo('#scheduleModal');
                }
            });

            $('#endDateTime').datetimepicker({
                format: format,
                timepicker: timepicker,
                onShow: function(ct) {
                    // Memindahkan datepicker ke dalam modal secara langsung
                    $('.xdsoft_datetimepicker').appendTo('#scheduleModal');
                }
            });
        }

        function modalReset() {
            $('#eventId').val('');
            $('#title').val('');
            $('#description').val('');
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

            // Hanya jika is_all_day dicentang, hapus waktu dari start dan end agar menjadi All Day event di FullCalendar
            if (postData.is_all_day) {
                // Jika event adalah "All Day", hanya tanggal saja yang digunakan, tanpa waktu.
                postData.start = moment(postData.start).format('YYYY-MM-DD');
                postData.end = moment(postData.start).add(1, 'days').format(
                    'YYYY-MM-DD'); // FullCalendar akan memahami bahwa ini event sehari penuh
            } else {
                // Jika bukan "All Day", gunakan format lengkap dengan waktu.
                postData.start = moment(postData.start).format('YYYY-MM-DD HH:mm:ss');
                postData.end = moment(postData.end).format('YYYY-MM-DD HH:mm:ss');
            }

            if (eventId) {
                url = url + '/' + eventId;
                postData._method = 'PUT';
            }
            // Query Ajax
            $.ajax({
                url: url,
                type: 'POST',
                data: postData,
                success: function(response) {
                    if (response.status == 'success') {
                        scheduleModal.close();
                        calendar.refetchEvents();
                    } else {
                        alert(response.message);
                    }
                }
            })
        }
    </script>
@endsection
