<template>
  <div>
    <a
      class="count-info"
      data-toggle="dropdown"
      href="#"
      aria-expanded="false"
      id="exPopover1-bottom"
    >
      <i class="fas fa-bell fa-lg font-size-23"></i>
      <b-badge pill variant="danger" v-if="totalMessages>0 && totalMessages<=9">{{totalMessages}}</b-badge>
      <b-badge pill variant="danger" v-if="totalMessages>9" id="info-large">9+</b-badge>
    </a>
    <b-popover :target="'exPopover1-bottom'" :placement="'bottomleft'" triggers="click blur">
      <h3 class="popover-header bg-light m-0 p-2">{{$t('Notifications')}}</h3>
      <ul class="list-unstyled tasklist m-2">
        <li v-if="messages.length == 0">
          {{$t('No Notifications Found')}}
          <hr>
        </li>
        <li v-for="(task, index) in messages" v-if="index <= 5" :key="`message-${index}`">
          <div class="d-flex align-items-end flex-column float-right">
            <small class="float-right muted" v-b-tooltip.hover :title="moment(task.created_at).format()">{{ moment(task.created_at).fromNow() }}</small>
            <div
              class="text-info float-right mt-1"
              style="cursor:pointer"
              @click="remove(task)"
              v-b-tooltip.hover
              :title="$t('Dismiss Alert')"
            ><i class="fa fa-trash"></i></div>
          </div>

          <h3>
            <i :class="icon(task)"></i>
            <a class="text-info" href="#" @click.stop="remove(task, task.url)">{{task.name}}</a>
          </h3>
          <div class="muted">
            {{task.processName}}
            <br>
            {{task.userName}}
          </div>
          <hr>
        </li>
        <li class="footer d-flex justify-content-between">
          <button
            v-if="messages.length != 0"
            class="btn btn-sm btn-outline-info"
            @click="removeAll"
          >{{$t('Dismiss All')}}</button>
          <a class="btn btn-sm btn-info" href="/notifications">{{$t('View All')}}</a>
        </li>
      </ul>
    </b-popover>
  </div>
</template>

<script>
import moment from "moment";
import { PopoverPlugin } from "bootstrap-vue"

Vue.use(PopoverPlugin);
export default {
  props: {
    messages: Array
  },
  watch: {
    messages(value, mutation) {
      //update the number of messages just whe the number has been initialized (in mounted)
      if (this.incrementTotalMessages) {
        this.updateTotalMessages();
        $(this.$el)
          .find(".dropdown")
          .dropdown("toggle");
      }
    }
  },
  data() {
    return {
      totalMessages: 0,
      incrementTotalMessages: false,
      arrowStyle: {
        top: "0px",
        left: "0px"
      }
    };
  },
  methods: {
    icon(task) {
      return ProcessMaker.$notifications.icons[task.type];
    },
    updateTotalMessages() {
      this.incrementTotalMessages = false;
      ProcessMaker.apiClient
        .get("/notifications?per_page=5&filter=unread")
        .then(response => {
          ProcessMaker.notifications.splice(0);
          response.data.data.forEach(function(element) {
            ProcessMaker.pushNotification(element);
          });
          this.totalMessages = response.data.meta.total;
          this.$nextTick(() => {
            this.incrementTotalMessages = true;
          });
        });
    },
    remove(message, redirectTo = null) {
      ProcessMaker.removeNotifications([message.id]).then(() => {
        if (this.totalMessages > 0) {
          this.totalMessages--;
        }
        if (redirectTo) {
          window.location.href = redirectTo
        }
      });
    },
    formatDateTime(iso8601) {
      return moment(iso8601).format("MM/DD/YY HH:mm");
    },
    removeAll() {
      let that = this;
      //Remove all notification of current user
      ProcessMaker.apiClient
        .put("/read_all_notifications", {
          id: ProcessMaker.user.id,
          type: "ProcessMaker\\Models\\User"
        })
        .then(() => {
          ProcessMaker.notifications.splice(
            0,
            ProcessMaker.notifications.length
          );
          that.totalMessages = 0;
        });
    }
  },
  mounted() {
    this.arrowStyle.top = $("#navbar-request-button").offset().top + 45 + "px";
    this.arrowStyle.left =
      $("#navbar-request-button").offset().left + 53 + "px";

    window.addEventListener("resize", () => {
      this.arrowStyle.top =
        $("#navbar-request-button").offset().top + 42 + "px";
      this.arrowStyle.left =
        $("#navbar-request-button").offset().left + 32 + "px";
    });

    this.updateTotalMessages();
  }
};
</script>

<style lang="scss" scoped>
.popover-header {
  font-size: 18px;
  font-weight: 600;
  margin: -12px;
  margin-top: -8px;
  margin-bottom: 18px;
  display: block;
}

.tasklist {
  font-size: 12px;
  width: 250px;
  margin-bottom: 6px;

  h3 {
    font-size: 14px;
    color: #3397e1;
  }

  .muted {
    color: #7b8792;
  }

  .footer {
    font-size: 14px;
    font-weight: normal;
    color: #3397e1;
    text-transform: uppercase;
  }
}

.count-info {
  color: #788793;
}

.count-info .badge {
  font-size: 10px;
  padding: 2px 5px;
  position: absolute;
  right: 88px;
  top: 17px;
}
.count-info #info-large {
  position: absolute;
  right: 83px;
  top: 17px;
}
</style>
