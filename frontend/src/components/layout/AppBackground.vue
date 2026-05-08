<template>
  <div class="apple-bg-visual">
    <div class="apple-bg-sphere apple-bg-sphere-1"></div>
    <div class="apple-bg-sphere apple-bg-sphere-2"></div>
    <div class="apple-bg-sphere apple-bg-sphere-3"></div>
  </div>
</template>

<style scoped>
.apple-bg-visual { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 0; overflow: hidden; pointer-events: none; }
.apple-bg-sphere { position: absolute; border-radius: 50%; filter: blur(60px); opacity: 0.6; animation: appleFloat 12s infinite alternate ease-in-out; }
.apple-bg-sphere-1 { width: 450px; height: 450px; background: #bae6fd; top: -10%; right: -5%; }
.apple-bg-sphere-2 { width: 350px; height: 350px; background: #fecdd3; bottom: -10%; left: -5%; animation-delay: -3s; }
.apple-bg-sphere-3 { width: 400px; height: 400px; background: #dbeafe; top: 30%; left: 25%; animation-delay: -6s; }

@keyframes appleFloat {
  0% { transform: translate(0, 0) scale(1) rotate(0deg); }
  50% { transform: translate(40px, -40px) scale(1.1) rotate(180deg); }
  100% { transform: translate(-30px, 30px) scale(0.9) rotate(360deg); }
}
</style>
