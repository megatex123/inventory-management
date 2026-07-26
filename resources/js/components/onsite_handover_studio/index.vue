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
  },
};
</script>
