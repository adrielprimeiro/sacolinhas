module.exports = {
  apps : [
    {
      name   : "tiktok-live",
      script : "./tiktok-service/server.js",
      watch  : false,
      max_memory_restart: '300M',
      env: {
        NODE_ENV: "production",
        PORT: 3001
      }
    },
    {
      name   : "insta-live",
      script : "./insta-service/server.js",
      watch  : false,
      max_memory_restart: '800M',
      env: {
        NODE_ENV: "production",
        PORT: 3002
      }
    }
  ]
};
