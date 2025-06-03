<template>
    <div class="vertice-layout">
      <router-view></router-view>
    </div>
  </template>
  
  <script>
  export default {
    name: 'VerticeLayout'
  }
  </script>
  
  <style scoped>
  .vertice-layout {
    padding: 20px;
    transition: margin-left 0.3s ease;
  }
  
  @media (max-width: 768px) {
    .vertice-layout {
      margin-left: 0;
    }
  }
  
  body.sidebar-collapsed .vertice-layout {
    margin-left: 60px;
  }
  </style>