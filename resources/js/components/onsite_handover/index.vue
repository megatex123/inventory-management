<template>
  <div class="container-fluid">
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border"></div>
    </div>
    <template v-else>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-0">OnSite Handover — {{ order.order_id }}</h4>
          <small class="text-muted">Report {{ onsiteHandover.report_id }} · Round {{ onsiteHandover.round }}</small>
        </div>
      </div>

      <report-info-section
        :api-base="apiBase"
        :initial-data="onsiteHandover"
        @saved="onReportInfoSaved"
      />
      <customer-info-section
        :api-base="apiBase"
        :initial-data="onsiteHandover"
        :customer-name="derived.customer_name"
        :customer-id="derived.customer_id"
        :contact-number="derived.contact_number"
        :email-address="derived.email_address"
        @saved="onCustomerInfoSaved"
      />
      <build-info-section
        :api-base="apiBase"
        :initial-data="onsiteHandover"
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
        :initial-data="onsiteHandover"
        :studio-inspection-report-completed="derived.studio_inspection_report_completed"
        :studio-inspection-report-id="derived.studio_inspection_report_id"
        :performance-testing-report-completed="derived.performance_testing_report_completed"
        :performance-testing-report-id="derived.performance_testing_report_id"
        @saved="onStudioDocsSaved"
      />
      <arrival-section
        :api-base="apiBase"
        :initial-data="onsiteHandover"
        @saved="onArrivalSaved"
      />
      <transportation-section
        :api-base="apiBase"
        :initial-data="onsiteHandover"
        @saved="onTransportationSaved"
      />
      <assembly-section
        :api-base="apiBase"
        :initial-data="onsiteHandover"
        @saved="onAssemblySaved"
      />
      <post-build-hardware-section
        :api-base="apiBase"
        :initial-data="onsiteHandover"
        @saved="onPostBuildHardwareSaved"
      />
      <post-build-software-section
        :api-base="apiBase"
        :initial-data="onsiteHandover"
        @saved="onPostBuildSoftwareSaved"
      />
      <customer-acceptance-section
        :api-base="apiBase"
        :initial-data="onsiteHandover"
        @saved="onCustomerAcceptanceSaved"
      />
      <acknowledgement-section
        :api-base="apiBase"
        :initial-data="onsiteHandover"
        @saved="onAcknowledgementSaved"
      />
    </template>
  </div>
</template>

<script>
import axios from 'axios';
import ReportInfoSection from './ReportInfoSection.vue';
import CustomerInfoSection from './CustomerInfoSection.vue';
import BuildInfoSection from './BuildInfoSection.vue';
import StudioDocsSection from './StudioDocsSection.vue';
import ArrivalSection from './ArrivalSection.vue';
import TransportationSection from './TransportationSection.vue';
import AssemblySection from './AssemblySection.vue';
import PostBuildHardwareSection from './PostBuildHardwareSection.vue';
import PostBuildSoftwareSection from './PostBuildSoftwareSection.vue';
import CustomerAcceptanceSection from './CustomerAcceptanceSection.vue';
import AcknowledgementSection from './AcknowledgementSection.vue';

export default {
  components: {
    ReportInfoSection,
    CustomerInfoSection,
    BuildInfoSection,
    StudioDocsSection,
    ArrivalSection,
    TransportationSection,
    AssemblySection,
    PostBuildHardwareSection,
    PostBuildSoftwareSection,
    CustomerAcceptanceSection,
    AcknowledgementSection,
  },
  data() {
    return {
      order: {},
      onsiteHandover: null,
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
      return `/api/order/${this.orderId}/onsite-handover/${this.round}`;
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
          this.onsiteHandover = data.onsite_handover;
          this.derived = {
            customer_name: data.order.customer ? data.order.customer.full_name : null,
            customer_id: data.order.customer ? data.order.customer.customer_id : null,
            contact_number: data.order.customer ? data.order.customer.phone : null,
            email_address: data.order.customer ? data.order.customer.email : null,
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
      this.onsiteHandover = data;
    },
    onCustomerInfoSaved(data) {
      this.onsiteHandover = data;
    },
    onBuildInfoSaved(data) {
      this.onsiteHandover = data;
    },
    onStudioDocsSaved(data) {
      this.onsiteHandover = data;
    },
    onArrivalSaved(data) {
      this.onsiteHandover = data;
    },
    onTransportationSaved(data) {
      this.onsiteHandover = data;
    },
    onAssemblySaved(data) {
      this.onsiteHandover = data;
    },
    onPostBuildHardwareSaved(data) {
      this.onsiteHandover = data;
    },
    onPostBuildSoftwareSaved(data) {
      this.onsiteHandover = data;
    },
    onCustomerAcceptanceSaved(data) {
      this.onsiteHandover = data;
    },
    onAcknowledgementSaved(data) {
      this.onsiteHandover = data;
    },
  },
};
</script>
