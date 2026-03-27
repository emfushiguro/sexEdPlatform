{{-- Pure Alpine.js mini calendar — no backend, shows current month, highlights today --}}
<div
    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4"
    x-data="{
        today: new Date(),
        current: new Date(),
        get monthName() {
            return this.current.toLocaleString('default', { month: 'long' });
        },
        get year() {
            return this.current.getFullYear();
        },
        get daysInMonth() {
            return new Date(this.current.getFullYear(), this.current.getMonth() + 1, 0).getDate();
        },
        get firstDayOfMonth() {
            return new Date(this.current.getFullYear(), this.current.getMonth(), 1).getDay();
        },
        get days() {
            let days = [];
            let blanks = this.firstDayOfMonth;
            for (let i = 0; i < blanks; i++) days.push(null);
            for (let d = 1; d <= this.daysInMonth; d++) days.push(d);
            return days;
        },
        isToday(day) {
            if (!day) return false;
            return day === this.today.getDate()
                && this.current.getMonth() === this.today.getMonth()
                && this.current.getFullYear() === this.today.getFullYear();
        },
        prevMonth() {
            this.current = new Date(this.current.getFullYear(), this.current.getMonth() - 1, 1);
        },
        nextMonth() {
            this.current = new Date(this.current.getFullYear(), this.current.getMonth() + 1, 1);
        },
    }"
>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-3">
        <button
            @click="prevMonth()"
            class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        >
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 18-6-6 6-6"/>
            </svg>
        </button>

        <span class="text-sm font-semibold text-gray-800 dark:text-white" x-text="monthName + ' ' + year"></span>

        <button
            @click="nextMonth()"
            class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        >
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"/>
            </svg>
        </button>
    </div>

    {{-- Day headers --}}
    <div class="grid grid-cols-7 mb-1">
        @foreach(['Su','Mo','Tu','We','Th','Fr','Sa'] as $day)
            <div class="text-center text-[10px] font-semibold text-gray-400 dark:text-gray-500 py-1">
                {{ $day }}
            </div>
        @endforeach
    </div>

    {{-- Day cells --}}
    <div class="grid grid-cols-7 gap-y-0.5">
        <template x-for="(day, index) in days" :key="index">
            <div class="flex items-center justify-center">
                <span
                    class="w-7 h-7 flex items-center justify-center rounded-full text-xs font-medium transition-colors"
                    :class="{
                        'text-white font-bold': isToday(day),
                        'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-default': day && !isToday(day),
                        'invisible': !day
                    }"
                    :style="isToday(day) ? 'background: linear-gradient(135deg, #A30EB2, #3B0CB1);' : ''"
                    x-text="day || ''"
                ></span>
            </div>
        </template>
    </div>
</div>
