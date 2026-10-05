using System;
using System.Diagnostics;
using System.IO;
using System.Net;
using System.Net.Sockets;
using System.Reflection;
using System.Threading;
using System.Windows.Forms;

namespace SenadorCocoLauncher
{
    internal static class Program
    {
        private const string AppUrl = "http://127.0.0.1:8000";
        private const string XamppPath = @"C:\xampp";
        private const int MySqlPort = 3306;

        [STAThread]
        private static void Main()
        {
            Application.EnableVisualStyles();

            string appRoot = FindApplicationRoot();

            if (string.IsNullOrWhiteSpace(appRoot))
            {
                MessageBox.Show(
                    "Could not find the Laravel app folder. Put this launcher inside the IT12_project folder.",
                    "Senador Coco",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Error
                );

                return;
            }

            try
            {
                EnsureMySqlRunning();

                if (!IsServerRunning())
                {
                    StartLaravelServer(appRoot);
                    WaitForServer();
                }

                OpenBrowser();
            }
            catch (Exception exception)
            {
                MessageBox.Show(
                    "Unable to start Senador Coco.\n\n" + exception.Message,
                    "Senador Coco",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Error
                );
            }
        }

        private static string FindApplicationRoot()
        {
            string executableDirectory = Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location);
            string parentDirectory = null;

            if (!string.IsNullOrWhiteSpace(executableDirectory))
            {
                DirectoryInfo parent = Directory.GetParent(executableDirectory);
                parentDirectory = parent == null ? null : parent.FullName;
            }

            string[] candidates =
            {
                executableDirectory,
                parentDirectory,
                Environment.CurrentDirectory,
            };

            foreach (string candidate in candidates)
            {
                if (!string.IsNullOrWhiteSpace(candidate) && File.Exists(Path.Combine(candidate, "artisan")))
                {
                    return candidate;
                }
            }

            return null;
        }

        private static void EnsureMySqlRunning()
        {
            if (IsMySqlRunning())
            {
                return;
            }

            string mysqlStartPath = Path.Combine(XamppPath, "mysql_start.bat");

            if (!File.Exists(mysqlStartPath))
            {
                throw new FileNotFoundException(
                    "XAMPP MySQL starter was not found. Please check that XAMPP is installed at C:\\xampp.",
                    mysqlStartPath
                );
            }

            ProcessStartInfo startInfo = new ProcessStartInfo
            {
                FileName = "cmd.exe",
                Arguments = "/c \"" + mysqlStartPath + "\"",
                WorkingDirectory = XamppPath,
                UseShellExecute = false,
                CreateNoWindow = true,
                WindowStyle = ProcessWindowStyle.Hidden,
            };

            Process.Start(startInfo);
            WaitForMySql();
        }

        private static bool IsMySqlRunning()
        {
            try
            {
                using (TcpClient client = new TcpClient())
                {
                    IAsyncResult result = client.BeginConnect("127.0.0.1", MySqlPort, null, null);
                    bool connected = result.AsyncWaitHandle.WaitOne(800);

                    if (!connected)
                    {
                        return false;
                    }

                    client.EndConnect(result);

                    return true;
                }
            }
            catch
            {
                return false;
            }
        }

        private static void WaitForMySql()
        {
            for (int attempt = 0; attempt < 40; attempt++)
            {
                if (IsMySqlRunning())
                {
                    return;
                }

                Thread.Sleep(500);
            }

            throw new TimeoutException("MySQL did not start. Open XAMPP Control Panel and check MySQL.");
        }

        private static bool IsServerRunning()
        {
            try
            {
                HttpWebRequest request = (HttpWebRequest) WebRequest.Create(AppUrl);
                request.Method = "GET";
                request.Timeout = 800;

                using (request.GetResponse())
                {
                    return true;
                }
            }
            catch
            {
                return false;
            }
        }

        private static void StartLaravelServer(string appRoot)
        {
            ProcessStartInfo startInfo = new ProcessStartInfo
            {
                FileName = "cmd.exe",
                Arguments = "/c php artisan serve --host=127.0.0.1 --port=8000",
                WorkingDirectory = appRoot,
                UseShellExecute = false,
                CreateNoWindow = true,
                WindowStyle = ProcessWindowStyle.Hidden,
            };

            Process.Start(startInfo);
        }

        private static void WaitForServer()
        {
            for (int attempt = 0; attempt < 30; attempt++)
            {
                if (IsServerRunning())
                {
                    return;
                }

                Thread.Sleep(500);
            }
        }

        private static void OpenBrowser()
        {
            ProcessStartInfo browser = new ProcessStartInfo
            {
                FileName = AppUrl,
                UseShellExecute = true,
            };

            Process.Start(browser);
        }
    }
}
