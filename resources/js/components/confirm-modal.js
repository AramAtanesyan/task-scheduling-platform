/**
 * Confirm Modal Component
 * 
 * Reusable confirmation dialog that can be triggered programmatically
 */

window.Vue.component('confirm-modal', {
  template: `
    <transition name="modal-fade">
      <div v-if="isVisible" class="modal-overlay" @click.self="handleCancel">
        <div class="modal confirm-modal">
          <div class="modal-header">
            <h3>{{ displayTitle }}</h3>
          </div>
          <div class="modal-body">
            <p>{{ displayMessage }}</p>
            <p v-if="error" class="error-message">{{ error }}</p>
          </div>
          <div class="modal-footer">
            <button 
              @click="handleCancel" 
              class="btn btn-secondary"
              :disabled="loading"
            >
              {{ displayCancelText }}
            </button>
            <button 
              @click="handleConfirm" 
              :class="['btn', displayDangerMode ? 'btn-danger' : 'btn-primary']"
              :disabled="loading"
            >
              <span v-if="loading" class="spinner"></span>
              <span v-else>{{ displayConfirmText }}</span>
            </button>
          </div>
        </div>
      </div>
    </transition>
  `,
  props: {
    show: {
      type: Boolean,
      default: false
    },
    title: {
      type: String,
      default: ''
    },
    message: {
      type: String,
      default: ''
    },
    confirmText: {
      type: String,
      default: ''
    },
    cancelText: {
      type: String,
      default: ''
    },
    dangerMode: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      visible: false,
      internalTitle: 'Confirm Action',
      internalMessage: 'Are you sure you want to proceed?',
      internalConfirmText: 'Confirm',
      internalCancelText: 'Cancel',
      internalDangerMode: false,
      loading: false,
      error: null,
      resolveCallback: null,
      rejectCallback: null,
      asyncCallback: null
    };
  },
  computed: {
    isVisible() {
      return this.show || this.visible;
    },
    displayTitle() {
      return this.title || this.internalTitle;
    },
    displayMessage() {
      return this.message || this.internalMessage;
    },
    displayConfirmText() {
      return this.confirmText || this.internalConfirmText;
    },
    displayCancelText() {
      return this.cancelText || this.internalCancelText;
    },
    displayDangerMode() {
      return this.dangerMode || this.internalDangerMode;
    }
  },
  methods: {
    // Programmatic usage (for backward compatibility)
    open(options = {}) {
      this.internalTitle = options.title || 'Confirm Action';
      this.internalMessage = options.message || 'Are you sure you want to proceed?';
      this.internalConfirmText = options.confirmText || 'Confirm';
      this.internalCancelText = options.cancelText || 'Cancel';
      this.internalDangerMode = options.dangerMode || false;
      this.asyncCallback = options.onConfirm || null;
      this.error = null;
      this.loading = false;
      this.visible = true;

      // Return a promise for easy async handling
      return new Promise((resolve, reject) => {
        this.resolveCallback = resolve;
        this.rejectCallback = reject;
      });
    },
    async handleConfirm() {
      if (this.asyncCallback) {
        // Programmatic usage with async callback
        this.loading = true;
        this.error = null;
        
        try {
          await this.asyncCallback();
          this.resolveCallback(true);
          this.close();
        } catch (error) {
          this.loading = false;
          this.error = error.message || 'An error occurred. Please try again.';
          this.rejectCallback(error);
        }
      } else if (this.show) {
        // Props-based usage - emit event
        this.$emit('confirm');
      } else {
        // Programmatic usage without async
        this.resolveCallback(true);
        this.close();
      }
    },
    handleCancel() {
      if (!this.loading) {
        if (this.show) {
          // Props-based usage - emit event
          this.$emit('cancel');
        } else {
          // Programmatic usage
          this.resolveCallback(false);
          this.close();
        }
      }
    },
    close() {
      this.visible = false;
      this.error = null;
      this.loading = false;
      this.asyncCallback = null;
    }
  }
});

