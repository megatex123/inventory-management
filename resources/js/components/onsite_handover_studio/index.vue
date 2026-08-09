<template>
  <div class="container-fluid">
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border"></div>
    </div>
    <template v-else>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-0">OnSite Handover (Studio) — {{ order.order_id }}</h4>
          <small class="text-muted">Report {{ onsiteHandoverStudio.report_id }} · Round {{ onsiteHandoverStudio.round }}</small>
        </div>
        <div>
          <router-link to="/orders/all" class="btn btn-outline-secondary mr-2"><i class="fas fa-arrow-left mr-1"></i> Back to Orders</router-link>
          <button class="btn btn-success mr-2" @click="printPdf">
            <i class="fas fa-file-pdf mr-1"></i> Print / PDF
          </button>
          <button
            class="btn btn-success"
            :disabled="!onsiteHandoverStudio || onsiteHandoverStudio.status === 'completed'"
            @click="markComplete"
          >
            <i class="fas fa-check-circle mr-1"></i>
            {{ onsiteHandoverStudio && onsiteHandoverStudio.status === 'completed' ? 'Completed' : 'Mark Complete' }}
          </button>
        </div>
      </div>

      <report-info-section
        :api-base="apiBase"
        :initial-data="onsiteHandoverStudio"
        @saved="onReportInfoSaved"
      />
      <build-info-section
        :api-base="apiBase"
        :initial-data="onsiteHandoverStudio"
        :quivicraft-id="derived.quivicraft_id"
        :quivicraft-plan="derived.quivicraft_plan"
        :quiviserve-id="derived.quiviserve_id"
        :quiviserve-customer-id="derived.quiviserve_customer_id"
        :quiviserve-plan="derived.quiviserve_plan"
        :quivicare-id="derived.quivicare_id"
        :quivicare-plan="derived.quivicare_plan"
        @saved="onBuildInfoSaved"
      />
      <studio-docs-section
        :api-base="apiBase"
        :initial-data="onsiteHandoverStudio"
        :studio-inspection-report-completed="derived.studio_inspection_report_completed"
        :studio-inspection-report-id="derived.studio_inspection_report_id"
        :performance-testing-report-completed="derived.performance_testing_report_completed"
        :performance-testing-report-id="derived.performance_testing_report_id"
        @saved="onStudioDocsSaved"
      />
      <arrival-section
        :api-base="apiBase"
        :initial-data="onsiteHandoverStudio"
        @saved="onArrivalSaved"
      />
      <post-transport-section
        :api-base="apiBase"
        :initial-data="onsiteHandoverStudio"
        @saved="onPostTransportSaved"
      />
      <post-handover-section
        :api-base="apiBase"
        :initial-data="onsiteHandoverStudio"
        @saved="onPostHandoverSaved"
      />
      <customer-acceptance-section
        :api-base="apiBase"
        :initial-data="onsiteHandoverStudio"
        @saved="onCustomerAcceptanceSaved"
      />
    </template>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import ReportInfoSection from './ReportInfoSection.vue';
import BuildInfoSection from './BuildInfoSection.vue';
import StudioDocsSection from './StudioDocsSection.vue';
import ArrivalSection from './ArrivalSection.vue';
import PostTransportSection from './PostTransportSection.vue';
import PostHandoverSection from './PostHandoverSection.vue';
import CustomerAcceptanceSection from './CustomerAcceptanceSection.vue';

export default {
  components: {
    ReportInfoSection,
    BuildInfoSection,
    StudioDocsSection,
    ArrivalSection,
    PostTransportSection,
    PostHandoverSection,
    CustomerAcceptanceSection,
  },
  data() {
    return {
      order: {},
      onsiteHandoverStudio: null,
      derived: {},
      loading: true,
    };
  },
  computed: {
    orderId() {
      return this.$route.params.id;
    },
    round() {
      return this.$route.params.round || 1;
    },
    apiBase() {
      return `/api/order/${this.orderId}/onsite-handover-studio/${this.round}`;
    },
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    fetchData() {
      this.loading = true;
      axios.get(this.apiBase)
        .then(res => {
          const data = res.data.data;
          this.order = data.order;
          this.onsiteHandoverStudio = data.onsite_handover_studio;
          this.derived = {
            studio_inspection_report_completed: data.studio_inspection_report_completed,
            studio_inspection_report_id: data.studio_inspection_report_id,
            performance_testing_report_completed: data.performance_testing_report_completed,
            performance_testing_report_id: data.performance_testing_report_id,
            quivicraft_id: data.quivicraft_id,
            quivicraft_plan: data.quivicraft_plan,
            quiviserve_id: data.quiviserve_id,
            quiviserve_customer_id: data.quiviserve_customer_id,
            quiviserve_plan: data.quiviserve_plan,
            quivicare_id: data.quivicare_id,
            quivicare_plan: data.quivicare_plan,
          };
        })
        .finally(() => {
          this.loading = false;
        });
    },
    onReportInfoSaved(data) {
      Object.assign(this.onsiteHandoverStudio, data);
    },
    onBuildInfoSaved(data) {
      Object.assign(this.onsiteHandoverStudio, data);
    },
    onStudioDocsSaved(data) {
      Object.assign(this.onsiteHandoverStudio, data);
    },
    onArrivalSaved(data) {
      Object.assign(this.onsiteHandoverStudio, data);
    },
    onPostTransportSaved(data) {
      Object.assign(this.onsiteHandoverStudio, data);
    },
    onPostHandoverSaved(data) {
      Object.assign(this.onsiteHandoverStudio, data);
    },
    onCustomerAcceptanceSaved(data) {
      Object.assign(this.onsiteHandoverStudio, data);
    },
    printPdf() {
      window.print();
    },
    markComplete() {
      axios.post(`${this.apiBase}/complete`)
        .then(res => {
          this.onsiteHandoverStudio = res.data.data;
          Swal.fire('Marked Complete!', 'This onsite handover is now marked as completed.', 'success');
        })
        .catch(() => Swal.fire('Error!', 'Failed to mark onsite handover complete', 'error'));
    },
  },
};
</script>

<style>
@media print {
    #accordionSidebar,
    #sidebarToggleTop,
    .topbar,
    .scroll-to-top {
        display: none !important;
    }

    .btn,
    .no-print,
    .photo-upload-btn,
    .remove-btn {
        display: none !important;
    }

    html, body {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    #wrapper,
    #content-wrapper,
    #content,
    #container-wrapper {
        display: block !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    .container-fluid {
        width: 100% !important;
        max-width: 100% !important;
        margin-left: auto !important;
        margin-right: auto !important;
        padding-left: 0.5in !important;
        padding-right: 0.5in !important;
        box-sizing: border-box !important;
    }

    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
        page-break-inside: avoid;
    }

    .table-bordered,
    .table-bordered th,
    .table-bordered td {
        border: 1px solid #000 !important;
    }

    .text-primary {
        color: #000 !important;
    }

    .badge {
        border: 1px solid #000 !important;
        background-color: #fff !important;
        color: #000 !important;
    }

    .form-control {
        border: none !important;
        background: transparent !important;
        padding: 0 !important;
        -webkit-appearance: none;
        appearance: none;
    }

    .print-only {
        display: inline !important;
        font-weight: 700;
    }
}
</style>
