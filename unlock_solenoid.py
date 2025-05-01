from gpiozero import OutputDevice
from time import sleep

# Setup the relay pin connected to the solenoid lock
relay_pin = 17  # Replace with the GPIO pin you're using

# Initialize the relay
solenoid_lock = OutputDevice(relay_pin, active_high=False, initial_value=False)

def unlock_solenoid():
    print("Unlocking solenoid lock...")
    solenoid_lock.on()  # Activate relay to unlock solenoid
    sleep(5)            # Keep unlocked for 5 seconds
    print("Locking solenoid lock...")
    solenoid_lock.off()  # Deactivate relay to lock solenoid

if __name__ == "__main__":
    unlock_solenoid()
