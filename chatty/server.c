// server.c
#include <stdlib.h>
#include <string.h>
#include <sys/socket.h>
#include <netinet/in.h>

int main() {
  int server, instance;
  socklen_t clilen;
  char buffer[256];
  struct sockaddr_in srv, cli;
  char html[] = "HTTP/1.1 200 OK\r\nConnection: close\r\nContent-Type: text/html;\
charset=UTF-8\r\n\r\n\
<html>\
<body>\
<script>\
var inactive = 0;\
...
</body>";
  int html_len = strlen(html);

  server = socket(AF_INET, SOCK_STREAM, 0);
  bzero((char *) &srv, sizeof(srv));
  srv.sin_family = AF_INET;
  srv.sin_addr.s_addr = INADDR_ANY;
  srv.sin_port = htons(8000);
  int opt = 1;
  setsockopt(server, SOL_SOCKET, SO_REUSEADDR, &opt, sizeof(opt));
  if (bind(server, (struct sockaddr *) &srv, sizeof(srv)) < 0) {
    perror("ERROR: bind");
    exit(1);
  }
  listen(server,5);
  clilen = sizeof(cli);
  do {
    instance = accept(server, (struct sockaddr *) &cli, &clilen);
    if (instance < 0) continue;
    bzero(buffer,256);
    read(instance,buffer,256);
    if (strstr(buffer, "GET / ")) write(instance, html, html_len);
    close(instance);
  } while(1);
  return 0;
}
