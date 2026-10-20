interface TempleCalendarDay {
  date: string;
  slots: Array<{
    id: string;
    puja_name: string;
    start: string;
    end: string;
    capacity: number;
    booked: number;
  }>;
}

interface TempleDashboardCalendarViewProps {
  calendar: TempleCalendarDay[];
}

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString('en-IN', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
  });
}

function formatTime(timeStr: string): string {
  return new Date(`2000-01-01T${timeStr}`).toLocaleTimeString('en-IN', {
    hour: '2-digit',
    minute: '2-digit',
  });
}

function renderSlot(slot: TempleCalendarDay['slots'][0]) {
  return (
    <div
      key={slot.id}
      className="bg-gray-50 rounded-lg p-4 flex items-center justify-between"
    >
      <div className="flex items-center gap-4">
        <div className="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
          <span className="text-sm font-medium text-indigo-700">
            {slot.puja_name.charAt(0).toUpperCase()}
          </span>
        </div>
        <div>
          <p className="font-medium text-gray-900">{slot.puja_name}</p>
          <p className="text-sm text-gray-500">
            {formatTime(slot.start)} - {formatTime(slot.end)}
          </p>
        </div>
      </div>
      <div className="flex items-center gap-4">
        <span className="text-sm text-gray-500">
          Capacity: {slot.capacity}
        </span>
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
          slot.booked >= slot.capacity ? 'bg-red-100 text-red-800' :
          slot.booked > slot.capacity * 0.7 ? 'bg-yellow-100 text-yellow-800' :
          'bg-green-100 text-green-800'
        }`}>
          {slot.booked} / {slot.capacity} booked
        </span>
      </div>
    </div>
  );
}

function renderDay(day: TempleCalendarDay) {
  return (
    <div key={day.date} className="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <div className="bg-gray-50 px-6 py-3 border-b border-gray-200">
        <h4 className="font-semibold text-gray-900">{formatDate(day.date)}</h4>
        <p className="text-sm text-gray-500">{day.slots.length} slot{day.slots.length !== 1 ? 's' : ''} scheduled</p>
      </div>
      <div className="p-4">
        {day.slots.length === 0 ? (
          <p className="text-gray-500 text-center py-4">No slots for this day</p>
        ) : (
          <div className="space-y-3">
            {day.slots.map(renderSlot)}
          </div>
        )}
      </div>
    </div>
  );
}

export function TempleDashboardCalendarView({ calendar }: { calendar: TempleCalendarDay[] }) {
  return (
    <div className="bg-white rounded-xl shadow-sm overflow-hidden">
      <div className="p-6 border-b">
        <h3 className="text-lg font-semibold text-gray-900">Calendar View</h3>
      </div>
      <div className="p-6">
        {calendar.length === 0 ? (
          <div className="text-center py-12">
            <p className="text-gray-500">No availability scheduled for this month.</p>
          </div>
        ) : (
          <div className="space-y-6">
            {calendar.map(renderDay)}
          </div>
        )}
      </div>
    </div>
  );
}