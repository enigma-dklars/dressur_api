# -*- coding: utf-8 -*-

import os
import time
import pyautogui
import pyperclip
import keyboard
import mysql.connector

start_time = time.time()

# ── Paramètres ────────────────────────────────────────────────────────────────
sleep_time = 15
limit      = 10

db_config = {
    'host':     '193.203.168.45',
    'port':     3306,
    'user':     'u920435648_dress_username',
    'password': 'DressurDS3@',
    'database': 'u920435648_dress_dbname',
}

speedClickAfterWrite = "yes"  # hyper | yes | no

# Coordonnées écran (mode lap_l_up)
lap_l_up_Back        = "598 , 208"
lap_l_up_Focus       = "366 , 438"
lap_l_up_SearchBarre = "308 , 222"

# ── Utilitaires ───────────────────────────────────────────────────────────────

def on_keyboard_event(e):
    if e.event_type == keyboard.KEY_DOWN and e.name == 'suppr':
        print("\nArrêt du script.\n")
        fin_code()
        os._exit(0)

def pyautogui_click(xy_str):
    x, y = (int(v.strip()) for v in xy_str.split(','))
    for _ in range(5):
        pyautogui.click(x, y)
        time.sleep(0.1)

def click_write_number_and_focus(number, search_bar, focus):
    pyautogui_click(search_bar)
    pyautogui.press('esc')
    pyautogui_click(search_bar)
    time.sleep(0.2)
    pyautogui.write(str(number))
    if speedClickAfterWrite == "hyper":
        time.sleep(3)
    elif speedClickAfterWrite == "yes":
        time.sleep(5)
    else:
        time.sleep(12)
    pyautogui_click(focus)
    time.sleep(1.5 if speedClickAfterWrite in ("hyper", "yes") else 12)

def fin_code():
    elapsed = time.time() - start_time
    h, rem = divmod(int(elapsed), 3600)
    m, s   = divmod(rem, 60)
    print(f"Durée : {h}h {m}m {s}s\n")

# ── Envoi d'un message ────────────────────────────────────────────────────────

def send_one(entry_id, sendto, message):
    tel = sendto.replace(' ', '').replace('+', '')

    click_write_number_and_focus(tel, lap_l_up_SearchBarre, lap_l_up_Focus)
    pyperclip.copy(message)
    pyautogui.hotkey('ctrl', 'v')
    time.sleep(1)
    pyautogui.press('enter')
    pyautogui.press('enter')
    time.sleep(1)

    pyautogui_click(lap_l_up_Back)
    pyautogui.press('esc')
    pyautogui.press('esc')
    pyautogui.press('esc')

    con = mysql.connector.connect(**db_config)
    cur = con.cursor()
    cur.execute(
        "UPDATE file_attente_whatsapp SET statut = 'envoye' WHERE id = %s",
        (entry_id,)
    )
    con.commit()
    cur.close()
    con.close()

# ── Boucle principale ─────────────────────────────────────────────────────────

def send_queue():
    con = mysql.connector.connect(**db_config)
    cur = con.cursor()
    cur.execute(
        "SELECT id, sendto, message FROM file_attente_whatsapp "
        "WHERE statut = 'en_attente' ORDER BY id ASC LIMIT %s",
        (limit,)
    )
    rows = cur.fetchall()
    cur.close()
    con.close()

    total = len(rows)
    print(f"{total} message(s) en attente.\n")

    for idx, (entry_id, sendto, message) in enumerate(rows):
        print(f"[{idx + 1}/{total}] id={entry_id} → {sendto}")
        send_one(entry_id, sendto, message)
        print("")
        if idx < total - 1:
            time.sleep(sleep_time)

# ── Point d'entrée ────────────────────────────────────────────────────────────

if __name__ == '__main__':
    pyautogui.hotkey('alt', 'tab')
    keyboard.hook(on_keyboard_event)

    try:
        send_queue()
    except SystemExit:
        pass

    pyautogui.hotkey('alt', 'tab')
    fin_code()
