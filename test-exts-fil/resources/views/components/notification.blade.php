<div x-data="{
    messages: [],
    add(e) {
        const eventDetail = Array.isArray(e.detail) ? e.detail[0] : e.detail;
        const { message, type } = eventDetail
        this.messages.unshift({ id: e.timeStamp, content: message, type: type || 'info' });
    },
    remove(message) {
        this.messages = this.messages.filter(i => i.id !== message.id);
    }
}" x-on:notify.window="add($event);" aria-live="assertive"
  class="pointer-events-none fixed top-14 inset-0 flex flex-col items-end px-4 py-6 space-y-4 sm:items-start sm:p-6">
  <template x-for="message in messages" :key="message.id" hidden>
    <div x-data="{
        show: false,
        progress: 100,
        init() {
            this.$nextTick(() => this.show = true)
            this.startProgress();
        },
        startProgress() {
            const duration = 8000;
            const interval = 10;
            const steps = duration / interval;
            const decrement = 100 / steps;
    
            const timer = setInterval(() => {
                this.progress -= decrement;
                if (this.progress <= 0) {
                    clearInterval(timer);
                    this.transitionOut();
                }
            }, interval);
        },
        transitionOut() {
            this.show = false
            setTimeout(() => this.remove(this.message), 1000)
        },
    }" class="flex w-full flex-col items-center space-y-4 sm:items-end">
      <div x-show="show" x-transition:enter="transition ease-out duration-300 transition"
        x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
        x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="pointer-events-auto w-full max-w-sm overflow-hidden relative ring-1 ring-black ring-opacity-5 dark:bg-slate-700"
        :class="{
            'bg-green-100 text-green-500': message.type === 'success',
            'bg-yellow-100 text-yellow-500': message.type === 'warning',
            'bg-red-100 text-red-500': message.type === 'danger',
            'bg-blue-100 text-blue-500': message.type === 'info',
        }">
        <div class="p-4">
          <div class="flex items-center">
            <template x-if="message.type === 'success'">
              <div
                class="nline-flex items-center justify-center p-1 flex-shrink-0 w-8 h-8 text-green-500 bg-green-100 rounded-lg dark:bg-green-800 dark:text-green-200">
                <x-heroicon-s-check-circle class="w-6 h-6 text-green-500" />
              </div>
            </template>

            <template x-if="message.type === 'warning'">
              <div
                class="nline-flex items-center justify-center p-1 flex-shrink-0 w-8 h-8 text-yellow-500 bg-yellow-100 rounded-lg dark:bg-yellow-800 dark:text-yellow-200">
                <x-heroicon-s-exclamation-circle class="w-6 h-6 text-yellow-500" />
              </div>
            </template>

            <template x-if="message.type === 'danger'">
              <div
                class="nline-flex items-center justify-center p-1 flex-shrink-0 w-8 h-8 text-red-500 bg-red-100 rounded-lg dark:bg-red-800 dark:text-red-200">
                <x-heroicon-c-x-circle class="w-6 h-6 text-red-500" />
              </div>
            </template>

            <template x-if="message.type === 'info'">
              <div
                class="nline-flex items-center justify-center p-1 flex-shrink-0 w-8 h-8 text-blue-500 bg-blue-100 rounded-lg dark:bg-blue-800 dark:text-blue-200">
                <x-heroicon-s-information-circle class="w-6 h-6 text-blue-500" />
              </div>
            </template>

            <div class="ml-3 flex w-0 flex-1 justify-between">
              <p x-text="message.content" class="w-0 flex-1 text-base font-normal"></p>
              <div class="flex flex-shrink-0">
                <button x-on:click="remove(message)" type="button"
                  class="inline-flex rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:hover:text-slate-300">
                  <span class="sr-only">{{ t('Close') }}</span>
                  <x-heroicon-m-x-mark class="h-5 w-5" />
                </button>
              </div>
            </div>
          </div>
        </div>
        <!-- Progress Bar -->
        <div class="absolute bottom-0 left-0 h-1 transition-all duration-75 ease-linear"
          :class="{
              'bg-green-500': message.type === 'success',
              'bg-yellow-500': message.type === 'warning',
              'bg-red-500': message.type === 'danger',
              'bg-blue-500': message.type === 'info'
          }"
          :style="'width: ' + progress + '%'">
        </div>
      </div>
    </div>
  </template>
</div>
@if (session('notification'))
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Small delay to ensure the page has loaded
      setTimeout(() => {
        window.dispatchEvent(new CustomEvent('notify', {
          detail: {!! json_encode(session('notification')) !!}
        }));
      }, 300);
    });
  </script>
@endif
