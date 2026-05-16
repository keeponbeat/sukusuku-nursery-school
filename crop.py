from PIL import Image
try:
    img = Image.open("/home/toru/.gemini/antigravity/brain/00c75aee-d2d4-4b45-9be9-07cfc230bb36/media__1773632989748.jpg")
    cropped = img.crop((120, 50, 1420, 260))
    cropped.save("images/logo_cropped.jpg")
    print("Crop complete")
except Exception as e:
    print(f"Error: {e}")
