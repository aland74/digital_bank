import urllib.request
import zipfile
import os

url = "https://bin.equinox.io/c/b34edqS7P7z/ngrok-v3-stable-windows-amd64.zip"
filename = "ngrok.zip"

print(f"Downloading {url}...")
try:
    urllib.request.urlretrieve(url, filename)
    print("Download complete. Extracting...")
    with zipfile.ZipFile(filename, 'r') as zip_ref:
        zip_ref.extractall(".")
    print("Extraction complete!")
except Exception as e:
    print(f"Error: {e}")
