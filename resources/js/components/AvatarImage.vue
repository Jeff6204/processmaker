<template>
  <span :class="classContainer">
    <template v-for="(value, key) in options">
      <a :href="value.id">
        <template v-if="value.src" class="align-center">
          <img
            :src="timestamp(value.src)"
            :width="sizeImage"
            :height="sizeImage"
            :class="image"
            :title="value.tooltip"
          >
        </template>
        <template v-else>
          <button
            class="avatar-image-button rounded-circle bg-warning border-0 align-middle text-white text-center text-uppercase text-nowrap"
            :style="styleButton"
            :title="value.tooltip"
            :href="value.id"
          >{{value.initials}}</button>
        </template>
      </a>
      <span v-if="!hideName" class="text-center text-capitalize text-nowrap m-1">{{value.name}}</span>
    </template>
  </span>
</template>

<script>
export default {
  props: [
    "size",
    "rounded",
    "classContainer",
    "classImage",
    "inputData",
    "hideName"
  ],
  data() {
    return {
      round: "circle",
      image: "m-1",
      styleButton: "width: 25px; height: 25px;",
      options: []
    };
  },
  watch: {
    inputData(value) {
      this.formatInputData(value);
    },
    size(value) {
      this.formatSize(value);
    },
    rounded(value) {
      this.formatRounded(value);
    },
    classImage(value) {
      this.formatClassImage(value);
    }
  },
  methods: {
    timestamp(src) {
      if (src.startsWith('data:image')) {
        // Do not add cache buster to base64 encoded image
        return src
      }
      return src + '?' + new Date().getTime();
    },
    default() {
      this.displayTitle = this.hideName === undefined ? false : this.hideName;
      this.formatRounded(this.rounded);
      this.formatClassImage(this.classImage);
      this.formatInputData(this.inputData);
      this.formatSize(this.size);
    },
    formatClassImage(value) {
      this.image = value;
    },
    formatRounded(value) {
      this.round = value ? value : "circle";
    },
    formatSize(size) {
      this.sizeImage = size ? size : "25";
      this.formatSizeButton(this.sizeImage);
    },
    formatSizeButton(size) {
      this.styleButton =
        "width: " +
        size +
        "px; height: " +
        size +
        "px; font-size:" +
        size / 2.5 +
        "px; padding:0; cursor: pointer;";
    },
    formatValue(value) {
      if (value === null) {
        value = {};
      }
      
      return {
        id: value.id ? "/profile/" + value.id : null,
        src: value.src ? value.src : value.avatar ? value.avatar : "",
        tooltip: value.tooltip
          ? value.tooltip
          : !this.displayTitle
          ? value.title
          : value.fullname
          ? value.fullname
          : "",
        name:
          value.name !== undefined
            ? value.name
            : value.fullname
            ? value.fullname
            : "",
        initials: value.initials
          ? value.initials
          : value.firstname && value.lastname
          ? value.firstname.match(/./u)[0] + value.lastname.match(/./u)[0]
          : ""
      };
    },
    formatInputData(data) {
      let options = [];
      if (data && Array.isArray(data)) {
        let that = this;
        data.forEach(value => {
          options.push(that.formatValue(value));
        });
      } else {
        options.push(this.formatValue(data));
      }
      this.options = options;
    }
  },
  mounted() {
    this.default();
  }
};
</script>

<style lang="scss" scoped>
  .avatar-image-button {
    outline: 0 !important;
    
    &:active,
    &:focus,
    &:hover {
      outline: 0;
    }
  }
</style>
