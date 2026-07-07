module.exports = {
  apps : [
    {
      name   : "tiktok-live",
      script : "./tiktok-service/server.js",
      watch  : false,
      max_memory_restart: '300M',
      env: {
        NODE_ENV: "production",
        PORT: 3001,
        LARAVEL_URL: "http://127.0.0.1/api/live-chat/message-batch"
      }
    },
    {
      name   : "insta-live",
      script : "./insta-service/server.js",
      watch  : false,
      max_memory_restart: '800M',
      env: {
        NODE_ENV: "production",
        PORT: 3002,
        LARAVEL_URL: "http://127.0.0.1/api/live-chat/message-batch"
      }
    }
  ]
};
