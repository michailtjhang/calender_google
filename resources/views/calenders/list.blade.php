@extends('calenders.app')

@section('content')
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
                            <input type="text" class="input input-bordered bg-white text-gray-800" id="startDate"
                                placeholder="Select Start Date" name="title">
                            <span id="startDateError" class="text-error text-sm"></span>
                        </div>
                        <div class="form-control">
                            <label class="label text-gray-700">End Date</label>
                            <input type="text" class="input input-bordered bg-white text-gray-800" id="endDate"
                                placeholder="Select End Date" name="title">
                            <span id="endDateError" class="text-error text-sm"></span>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-action">
                <button type="button" id="cancelBtn" class="btn" onclick="scheduleModal.close()">Close</button>
                <button type="button" id="saveBtn" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </dialog>

    <script src='https://cdn.jsdelivr.net/npm/fullcalendar/index.global.min.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar')
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                dateClick: function(info) {
                    console.log(info)
                    scheduleModal.showModal()
                }
            })
            calendar.render()
        })
    </script>
@endsection
