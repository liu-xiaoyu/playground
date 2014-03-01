// Server.java
import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.DataInputStream;
import java.io.DataOutputStream;
import java.io.File;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.net.ServerSocket;
import java.net.Socket;
import java.util.Scanner;

public class Server {
  private static String readFile(String pathname) throws IOException {
    File file = new File(pathname);
    StringBuilder fileContents = new StringBuilder((int) file.length());
    Scanner scanner = new Scanner(file);
    try {
      while (scanner.hasNextLine())
        fileContents.append(scanner.nextLine() + "\n");
      return fileContents.toString();
    } finally {
      scanner.close();
    }
  }

  public static void main(String[] args) throws Exception {
    final String html = readFile("test.html");
    try (ServerSocket socket = new ServerSocket(8000)) {
      while (true) {
        final Socket client = socket.accept();
        try (Socket c = client) {
          while (true) {
            DataInputStream dis = new DataInputStream(c.getInputStream());
            DataOutputStream dos = new DataOutputStream(c.getOutputStream());
            BufferedReader in = new BufferedReader(new InputStreamReader(dis));
            BufferedWriter out = new BufferedWriter(new OutputStreamWriter(dos));
            String recv = in.readLine();
            if (recv != null && recv.indexOf("GET / ") == 0)
              out.write("HTTP/1.1 200 OK\r\nConnection: close\r\n"
                  + "Content-Type: text/html; charset=UTF-8\r\n\r\n" + html);
            // using StringBuilder was slower ...
            out.close();
            in.close();
          }
        } catch (IOException e) {
          // ok
        }
      }
    } catch (IOException e) {
      System.out.println(e);
} } }
